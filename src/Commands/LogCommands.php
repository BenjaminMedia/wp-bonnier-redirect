<?php

namespace Bonnier\WP\Redirect\Commands;

use Bonnier\WP\Redirect\Observers\CategorySubject;
use Bonnier\WP\Redirect\Observers\Loggers\CategoryObserver;
use Bonnier\WP\Redirect\Observers\Loggers\PostObserver;
use Bonnier\WP\Redirect\Observers\Loggers\TagObserver;
use Bonnier\WP\Redirect\Observers\PostSubject;
use Bonnier\WP\Redirect\Observers\TagSubject;
use Bonnier\WP\Redirect\WpBonnierRedirect;
use cli\progress\Bar;
use Illuminate\Support\Collection;
use function WP_CLI\Utils\make_progress_bar;

class LogCommands extends \WP_CLI_Command
{
    /** @var PostSubject */
    private $postSubject;
    /** @var CategorySubject */
    private $categorySubject;
    /** @var TagSubject */
    private $tagSubject;

    /**
     * Seeds the log table
     *
     * ## EXAMPLES
     *     wp bonnier redirect logs seed
     */
    public function seed()
    {
        $logRepo = WpBonnierRedirect::instance()->getLogRepository();
        $this->postSubject = new PostSubject();
        $this->postSubject->attach(new PostObserver($logRepo));
        $this->categorySubject = new CategorySubject();
        $this->categorySubject->attach(new CategoryObserver($logRepo));
        $this->tagSubject = new TagSubject();
        $this->tagSubject->attach(new TagObserver($logRepo));

        $postTypes = collect(get_post_types(['public' => true]))->reject('attachment');
        \WP_CLI::line(sprintf('Found %s post types', $postTypes->count()));
        $postTypes->each(function (string $postType) {
            \WP_CLI::line(sprintf('Fetching posts with post type \'%s\'...', $postType));
            $args = [
                'post_type' => $postType,
                'posts_per_page' => 100,
                'paged' => 1,
                'order' => 'ASC',
                'orderby' => 'ID'
            ];
            $posts = query_posts($args);
            while ($posts) {
                $this->registerPosts(collect($posts), $args['paged']);

                $args['paged']++;
                $posts = query_posts($args);
            }
        });

        collect(['category', 'post_tag'])->each(function (string $taxonomy) {
            \WP_CLI::line(sprintf('Fetching terms with taxonomy \'%s\'', $taxonomy));
            $terms = collect(get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ]));
            $this->registerTerms($terms);
        });

        \WP_CLI::success('Seeded the log table!');
    }

    private function registerPosts(Collection $posts, $page)
    {
        $count = $posts->count();
        /** @var Bar $progress */
        $progress = make_progress_bar(
            sprintf(
                'Handling %s posts on page %s of type \'%s\'',
                number_format($count),
                $page,
                $posts->first()->post_type
            ),
            $count
        );
        $posts->each(function (\WP_Post $post) use ($progress) {
            $this->postSubject->setPost($post)
                ->notify();
            $progress->tick();
        });
        $progress->finish();
    }

    private function registerTerms(Collection $terms)
    {
        $count = $terms->count();
        /** @var Bar $progress */
        $progress = make_progress_bar(
            sprintf('Handling %s terms of type \'%s\'', number_format($count), $terms->first()->taxonomy),
            $count
        );

        $terms->each(function (\WP_Term $term) use ($progress) {
            if ($term->taxonomy === 'category') {
                $this->categorySubject->setCategory($term)
                    ->setType(CategorySubject::UPDATE)
                    ->notify();
            } else {
                $this->tagSubject->setTag($term)
                    ->setType(TagSubject::UPDATE)
                    ->notify();
            }
            $progress->tick();
        });
        $progress->finish();
    }
}

<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Create;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Database\DB;
use Bonnier\WP\Redirect\Observers\Observers;
use Bonnier\WP\Redirect\Repositories\LogRepository;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;
use Bonnier\WP\Redirect\WpBonnierRedirect;

class ValidationTest extends ControllerTestCase
{
    public function _setUp()
    {
        parent::_setUp();

        Observers::bootstrap($this->logRepository, $this->redirectRepository);
    }

    public function testCannotCreateRedirectWithEmptyFrom()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '',
            'redirect_to' => '/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame('The \'from\'-value cannot be empty!', $errors['redirect_from']);

        $this->assertNull($this->findAllRedirects());
    }

    public function testCannotCreateRedirectWithEmptyTo()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/from/slug',
            'redirect_to' => '',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_to', $errors);
        $this->assertSame('The \'to\'-value cannot be empty!', $errors['redirect_to']);

        $this->assertNull($this->findAllRedirects());
    }

    /**
     * @dataProvider destructiveRedirectsProvider
     *
     * @param string $from
     * @param string $error
     */
    public function testCannotCreateDestructiveRedirects(string $from, string $error)
    {
        $request = $this->createPostRequest([
            'redirect_from' => $from,
            'redirect_to' => '/impossible/redirect',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());

        $errors = $crudController->getValidationErrors();
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('redirect_from', $errors);
        $this->assertSame($error, $errors['redirect_from']);

        $this->assertNull($this->findAllRedirects());
    }

    /**
     * @dataProvider sameFromToProvider
     *
     * @param string $fromUrl
     * @param string $toUrl
     * @param bool $identical
     */
    public function testCannotCreateRedirectWithSameFromAndTo(string $fromUrl, string $toUrl, bool $identical = false)
    {
        if ($identical) {
            $this->assertSame($fromUrl, $toUrl);
        } else {
            $this->assertNotSame($fromUrl, $toUrl);
        }
        $request = $this->createPostRequest([
            'redirect_from' => $fromUrl,
            'redirect_to' => $toUrl,
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeIs($crudController->getNotices(), 'error', 'From and to urls seems identical!');

        $this->assertNull($this->findAllRedirects());
    }

    public function testCannotCreateRedirectWithoutSpecifyingLocale()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/from/this/slug',
            'redirect_to' => '/to/this/slug',
            'redirect_locale' => '',
            'redirect_code' => 301,
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());
        $this->assertArrayHasKey('redirect_locale', $crudController->getValidationErrors());
    }

    /**
     * @dataProvider wpPostProvider
     * @param string $postType
     */
    public function testCannotCreateRedirectOnLiveWPPost(string $postType)
    {
        $post = $this->factory()->post->create_and_get(['post_type' => $postType, 'post_status' => 'publish']);

        $path = get_permalink($post);

        $request = $this->createPostRequest([
            'redirect_from' => $path,
            'redirect_to' => '/new/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $validationErrors = $crudController->getValidationErrors();

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());
        $this->assertArrayHasKey('redirect_from', $validationErrors);
        $this->assertSame(
            'A post or term with this slug is published!',
            $validationErrors['redirect_from']
        );
    }

    /**
     * @dataProvider wpTermProvider
     *
     * @param string $taxonomy
     */
    public function testCannotCreateRedirectOnLiveWPTerm(string $taxonomy)
    {
        $term = $this->factory()->term->create_and_get(['taxonomy' => $taxonomy]);

        $path = get_term_link($term->term_id, $taxonomy);

        $request = $this->createPostRequest([
            'redirect_from' => $path,
            'redirect_to' => '/new/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = $this->getCrudController($request);

        $validationErrors = $crudController->getValidationErrors();

        $this->assertNoticeWasInvalidInputMessage($crudController->getNotices());
        $this->assertArrayHasKey('redirect_from', $validationErrors);
        $this->assertSame(
            'A post or term with this slug is published!',
            $validationErrors['redirect_from']
        );
    }

    /**
     * @dataProvider wpPostProvider
     * @param string $postType
     */
    public function testCanCreateRedirectOnLiveWPPostWithFilter($postType)
    {
        $post = $this->factory()->post->create_and_get(['post_type' => $postType, 'post_status' => 'publish']);

        $path = get_permalink($post);

        add_filter(WpBonnierRedirect::FILTER_SLUG_IS_LIVE, function (bool $isLive) {
            return false;
        }, 10);

        $request = $this->createPostRequest([
            'redirect_from' => $path,
            'redirect_to' => '/new/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();

        $this->assertCount(1, $redirects);
        $this->assertManualRedirect($redirects->first(), rtrim(parse_url($path, PHP_URL_PATH), '/'), '/new/destination');
    }

    public function destructiveRedirectsProvider()
    {
        return [
            'Wildcard /*' => ['/*', 'You cannot create this destructive wildcard redirect!'],
            'Wildcard *' => ['*', 'You cannot create this destructive wildcard redirect!'],
            'Frontpage' => ['/', 'You cannot create a redirect from the frontpage!']
        ];
    }

    public function sameFromToProvider()
    {
        return [
            'Identical' => ['/from/slug', '/from/slug', true],
            'Same, but to has domain' => ['/from/slug', home_url('/from/slug')],
            'Same, but slashes are different' => ['from/slug/', '/from/slug'],
            'Same, but query are ordered different' => ['/from/slug?c=d&a=b', 'from/slug/?a=b&c=d'],
        ];
    }

    public function wpPostProvider()
    {
        return collect(get_post_types(['public' => true]))
            ->reject('attachment')
            ->mapWithKeys(function (string $postType) {
                return [$postType => [$postType]];
            })->toArray();
    }

    public function wpTermProvider()
    {
        return [
            'Tag' => ['post_tag'],
            'Category' => ['category']
        ];
    }
}

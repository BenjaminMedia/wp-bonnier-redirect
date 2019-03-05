<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Tag;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class SlugChangeTest extends ObserverTestCase
{
    public function testChangeTagSlugCreatesRedirect()
    {
        $tag = $this->getTag([
            'name' => 'Lizards',
            'slug' => 'lizards'
        ]);

        $this->assertSame('/lizards', $this->getTagSlug($tag));

        wp_update_term($tag->term_id, $tag->taxonomy, [
            'name' => 'Reptiles',
            'slug' => 'reptiles',
        ]);

        $this->assertSame('/reptiles', $this->getTagSlug($tag));

        try {
            $redirects = $this->redirectRepository->findAll();
        } catch (\Exception $exception) {
            $this->fail(sprintf('Failed finding redirects (%s)', $exception->getMessage()));
            return;
        }
        $this->assertCount(1, $redirects);
        $redirect = $redirects->first();
        $this->assertRedirect(
            $tag->term_id,
            $redirect,
            '/lizards',
            '/reptiles',
            'tag-slug-change'
        );
    }
}

<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Tag;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class StatusChangeTest extends ObserverTestCase
{
    public function testDeletingTagRedirectsToFrontpage()
    {
        $tag = $this->getTag([
            'name' => 'Lizards',
            'slug' => 'lizards'
        ]);

        $this->assertSame('/lizards', $this->getTagSlug($tag));

        wp_delete_term($tag->term_id, $tag->taxonomy);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $tag->term_id,
            $redirects->first(),
            '/lizards',
            '/',
            'tag-deleted'
        );
    }
}

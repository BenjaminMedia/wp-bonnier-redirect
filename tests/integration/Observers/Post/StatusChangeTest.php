<?php

namespace Bonnier\WP\Redirect\Tests\integration\Observers\Post;

use Bonnier\WP\Redirect\Tests\integration\Observers\ObserverTestCase;

class StatusChangeTest extends ObserverTestCase
{
    public function testTrashedPostCreatesRedirectToParentCategory()
    {
        $parentCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);
        $postCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $parentCategory->term_id,
        ]);

        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$postCategory->term_id],
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'trash',
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-trash'
        );
    }

    public function testUnpublishPostCreatesRedirectToParentCategory()
    {
        $parentCategory = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur',
        ]);
        $postCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $parentCategory->term_id,
        ]);

        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$postCategory->term_id],
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft',
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );
    }

    public function testCanUnpublishAndRepublishPostWithNewSlug()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $subCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $category->term_id,
        ]);
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );

        $this->updatePost($post->ID, [
            'post_name' => 't-rex-is-awesome',
            'post_status' => 'publish'
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex-is-awesome', $this->getPostSlug($post));

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->first(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous/t-rex-is-awesome',
            'post-slug-change'
        );
    }

    public function testCanUnpublishAndRepublishPostWithSameSlug()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $subCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $category->term_id,
        ]);
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'draft'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-draft'
        );

        $this->updatePost($post->ID, [
            'post_status' => 'publish'
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->assertNull($this->redirectRepository->findAll());
    }

    public function testCanDeletePostAndCreateNewPostWithSameSlug()
    {
        $category = $this->getCategory([
            'name' => 'Dinosaur',
            'slug' => 'dinosaur'
        ]);
        $subCategory = $this->getCategory([
            'name' => 'Carnivorous',
            'slug' => 'carnivorous',
            'parent' => $category->term_id,
        ]);
        $post = $this->getPost([
            'post_title' => 'T-Rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);
        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($post));

        $this->updatePost($post->ID, [
            'post_status' => 'trash'
        ]);

        $redirects = $this->redirectRepository->findAll();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            $post,
            $redirects->last(),
            '/dinosaur/carnivorous/t-rex',
            '/dinosaur/carnivorous',
            'post-trash'
        );

        $newPost = $this->getPost([
            'post_title' => 'New T-rex',
            'post_name' => 't-rex',
            'post_category' => [$subCategory->term_id]
        ]);

        $this->assertSame('/dinosaur/carnivorous/t-rex', $this->getPostSlug($newPost));

        $this->assertNull($this->redirectRepository->findAll());
    }
}

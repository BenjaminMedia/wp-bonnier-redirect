<?php

namespace Bonnier\WP\Redirect\Tests\integration\Controllers\CrudController\Create;

use Bonnier\WP\Redirect\Controllers\CrudController;
use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use Bonnier\WP\Redirect\Helpers\UrlHelper;
use Bonnier\WP\Redirect\Tests\integration\Controllers\ControllerTestCase;

class CreateTest extends ControllerTestCase
{
    public function testCanCreateNewRedirect()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/example/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);
        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();

        $this->assertCount(1, $redirects);
        $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');
    }

    public function testCannotCreateMultipleRedirectsWithSameFrom()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/example/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $this->getCrudController($request);

        $redirects = $this->findAllRedirects();
        $this->assertManualRedirect($redirects->first(), '/example/from/slug', '/example/to/slug');

        $newRequest = $this->createPostRequest([
            'redirect_from' => '/example/from/slug',
            'redirect_to' => '/new/example/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($newRequest);

        $this->assertNoticeIs(
            $crudController->getNotices(),
            'error',
            'A redirect with the same \'from\' and \'locale\' already exists!'
        );

        $newRedirects = $this->findAllRedirects();
        $this->assertCount(1, $newRedirects);
        $this->assertManualRedirect($newRedirects->first(), '/example/from/slug', '/example/to/slug');
    }

    public function testCreatingManualRedirectUpdatesOlderToAvoidChains()
    {
        $redirect = $this->createRedirect(
            '/this/example/path',
            '/new/page/slug',
            'post-slug-change',
            101
        );
        $this->assertRedirectCreated($redirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/new/page/slug',
            'redirect_to' => '/final/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $newRedirects = $this->findAllRedirects();
        $this->assertCount(2, $newRedirects);

        $this->assertRedirect(
            101,
            $newRedirects->first(),
            '/this/example/path',
            '/final/destination',
            'post-slug-change'
        );
        $this->assertManualRedirect(
            $newRedirects->last(),
            '/new/page/slug',
            '/final/destination',
            'da'
        );
    }

    public function testCreatingReverseRedirectOfExistingRedirectDeletesOldRedirect()
    {
        $oldRedirect = $this->createRedirect('/slug/alfa', '/slug/bravo');
        $this->assertRedirectCreated($oldRedirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/slug/bravo',
            'redirect_to' => '/slug/alfa',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);

        $this->assertRedirect(
            0,
            $redirects->first(),
            '/slug/bravo',
            '/slug/alfa',
            'manual'
        );
    }

    public function testCanCreateRedirectWithToWhichAlreadyExists()
    {
        $existingRedirect = $this->createRedirect('/from/something', '/to/destination');

        $this->assertRedirectCreated($existingRedirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/another/from/slug',
            'redirect_to' => '/to/destination',
            'redirect_locale' => 'da',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(2, $redirects);
        $this->assertSameRedirects($existingRedirect, $redirects->first());
        $this->assertRedirect(
            0,
            $redirects->last(),
            '/another/from/slug',
            '/to/destination',
            'manual'
        );
    }

    public function testCanCreateWildcardRedirect()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/polopoly.jsp*',
            'redirect_to' => '/',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/polopoly.jsp*',
            '/',
            'manual'
        );
        $this->assertTrue($redirects->first()->isWildcard());
    }

    public function testCanCreateRedirectThatKeepsQueryParams()
    {
        $request = $this->createPostRequest([
            'redirect_from' => '/from/slug',
            'redirect_to' => '/to/slug',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
            'redirect_keep_query' => '1'
        ]);

        $crudController = $this->getCrudController($request);

        $this->assertNoticeWasSaveRedirectMessage($crudController->getNotices());

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->first(),
            '/from/slug',
            '/to/slug',
            'manual'
        );
        $this->assertTrue($redirects->first()->keepsQuery());
    }

    public function testCreatingRedirectWithToWhichAlreadyExistsInFromAvoidsMakingChains()
    {
        $existingRedirect = $this->createRedirect('/original/from', '/final/destination');

        $this->assertRedirectCreated($existingRedirect);

        $request = $this->createPostRequest([
            'redirect_from' => '/another/from',
            'redirect_to' => '/original/from',
            'redirect_locale' => 'da',
            'redirect_code' => 301,
        ]);

        $crudController = $this->getCrudController($request);

        $notices = $crudController->getNotices();
        $expectedNotices = [
            ['type' => 'success', 'message' => 'The redirect was saved!'],
            ['type' => 'warning', 'message' => 'The redirect was chaining, and its \'to\'-url has been updated!'],
        ];
        $this->assertNotices($expectedNotices, $notices);

        $redirectsAfter = $this->findAllRedirects();
        $this->assertCount(2, $redirectsAfter);
        $this->assertSameRedirects($existingRedirect, $redirectsAfter->first());
        $this->assertRedirect(
            0,
            $redirectsAfter->last(),
            '/another/from',
            '/final/destination',
            'manual'
        );
    }

    /**
     * @dataProvider wpPostProvider
     *
     * @param $postType
     */
    public function testCanCreateRedirectEvenThoughLiveUrlIsOnAnotherLocale($postType)
    {
        LocaleHelper::setLanguageList(['da', 'fi']);

        /** @var \WP_Post $post */
        $post = $this->factory()->post->create_and_get(['post_type' => $postType, 'post_status' => 'publish']);

        $path = get_permalink($post);

        $this->assertNotEquals('fi', LocaleHelper::getPostLocale($post->ID));

        $request = $this->createPostRequest([
            'redirect_from' => $path,
            'redirect_to' => '/new/destination',
            'redirect_locale' => 'fi',
            'redirect_code' => 301
        ]);

        $crudController = $this->getCrudController($request);

        $notices = $crudController->getNotices();
        $expectedNotices = [
            ['type' => 'success', 'message' => 'The redirect was saved!'],
        ];
        $this->assertNotices($expectedNotices, $notices);

        $redirects = $this->findAllRedirects();
        $this->assertCount(1, $redirects);
        $this->assertRedirect(
            0,
            $redirects->last(),
            UrlHelper::normalizePath($path),
            '/new/destination',
            'manual'
        );
    }

    public function wpPostProvider()
    {
        return collect(get_post_types(['public' => true]))
            ->reject('attachment')
            ->mapWithKeys(function (string $postType) {
                return [$postType => [$postType]];
            })->toArray();
    }
}

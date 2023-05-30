<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Winter\Blog\Models\Post as PostModel;

class PopularPosts extends ComponentBase
{
    /**
     * A collection of popular posts to display
     *
     * @var Collection
     */
    public $posts;

    /**
     * @var string Reference to the page name for linking to posts.
     */
    public $postPage;

    /**
     * Component Details
     *
     * @return array
     */
    public function componentDetails(): array
    {
        return [
            'name'          => 'Popular Post',
            'description'   => 'Display Popular Post by Views.'
        ];
    }

    /**
     * Component Properties
     *
     * @return array
     */
    public function defineProperties(): array
    {
        return [
            'amount' => [
                'title'             => 'spanjaan.bloghub::lang.components.popularPosts.amount',
                'description'       => 'spanjaan.bloghub::lang.components.popularPosts.amount_comment',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'spanjaan.bloghub::lang.components.popularPosts.amount_validation',
                'default'           => '3',
            ],
            'postPage' => [
                'title'             => 'spanjaan.bloghub::lang.components.popularPosts.post_page',
                'description'       => 'spanjaan.bloghub::lang.components.popularPosts.post_page_comment',
                'type'              => 'dropdown',
                'default'           => 'blog/post',
                'group'             => 'spanjaan.bloghub::lang.components.popularPosts.group_links',
            ]
        ];
    }

    /**
     * Get Post Page Option
     *
     * @return array
     */
    public function getPostPageOptions(): array
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
     * Run
     *
     * @return void
     */
    public function onRun(): void
    {
        $this->postPage = $this->page['postPage'] = $this->property('postPage');
        $this->posts = $this->page['posts'] = $this->listPopularPosts();

    }


    /**
     * Load popular posts
     *
     * @return mixed
     */
    protected function listPopularPosts()
    {
        $query = PostModel::where('published', true)
            ->orderBy('spanjaan_bloghub_views', 'desc');

        $amount = intval($this->property('amount'));
        $query->limit($amount);

        return $query->get()->each(fn ($post) => $post->setUrl($this->postPage, $this->controller));
    }


}

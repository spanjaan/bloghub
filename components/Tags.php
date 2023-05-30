<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Components;

use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Spanjaan\BlogHub\Models\Tag as TagModel;

class Tags extends ComponentBase
{
    /**
     * A collection of tags to display
     *
     * @var Collection
     */
    public $tags;

    /**
     * @var string Reference to the page name for linking to categories.
     */
    public $tagPage;

    /**
     * @var string Slug of the currently selected tag, if any.
     */
    public $currentTagSlug;

    /**
     * Component Details
     *
     * @return void
     */
    public function componentDetails()
    {
        return [
            'name'          => 'spanjaan.bloghub::lang.components.tags.label',
            'description'   => 'spanjaan.bloghub::lang.components.tags.comment'
        ];
    }

    /**
     * Component Properties
     *
     * @return void
     */
    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'winter.blog::lang.settings.post_slug',
                'description' => 'winter.blog::lang.settings.post_slug_description',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
            'tagPage' => [
                'title'             => 'spanjaan.bloghub::lang.components.tags.tags_page',
                'description'       => 'spanjaan.bloghub::lang.components.tags.tags_page_comment',
                'type'              => 'dropdown',
                'default'           => 'blog/tag',
                'group'             => 'rainlab.blog::lang.settings.group_links',
            ],
            'onlyPromoted' => [
                'title'             => 'spanjaan.bloghub::lang.components.tags.only_promoted',
                'description'       => 'spanjaan.bloghub::lang.components.tags.only_promoted_comment',
                'type'              => 'checkbox',
                'default'           => '0'
            ],
            'amount' => [
                'title'             => 'spanjaan.bloghub::lang.components.tags.amount',
                'description'       => 'spanjaan.bloghub::lang.components.tags.amount_comment',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'spanjaan.bloghub::lang.components.tags.amount_validation',
                'default'           => '10',
            ]
        ];
    }

    /**
     * Get available CMS Pages for Tag Archive
     *
     * @return void
     */
    public function getTagPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
    /**
     * Run
     *
     * @return void
     */
    public function onRun()
    {
        $this->currentTagSlug = $this->page['currentTagSlug'] = $this->property('slug');
        $this->tagPage = $this->page['tagPage'] = $this->property('tagPage');
        $this->tags = $this->page['tags'] = $this->listTags();

    }


    /**
    * Load popular tags
    *
    * @return mixed
    */
    protected function listTags()
    {
        $query = TagModel::withCount('posts')
            ->whereHas('posts', function ($query) {
                $query->where('published', true);
            })
            ->orderBy('posts_count', 'desc');

        if ($this->property('onlyPromoted') === '1') {
            $query->where('promote', '1');
        }

        $amount = intval($this->property('amount'));
        $query->limit($amount);

        return $query->get()->each(fn ($tag) => $tag->setUrl($this->tagPage, $this->controller));
    }


}

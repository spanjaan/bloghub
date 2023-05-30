<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Components;

use Backend\Models\User as BackendUser;
use Winter\Blog\Components\Posts;
use Winter\Blog\Models\Post;

class PostsByAuthor extends Posts
{
    /**
     * The post list filtered by this author model.
     *
     * @var ?BackendUser
     */
    public $author = null;

    /**
     * Declare Component Details
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name'          => 'spanjaan.bloghub::lang.components.author.label',
            'description'   => 'spanjaan.bloghub::lang.components.author.comment'
        ];
    }

    /**
    * Component Properties
    *
    * @return void
    */
    public function defineProperties()
    {
        $properties['authorFilter'] = [
            'title'         => 'spanjaan.bloghub::lang.components.author.filter',
            'description'   => 'spanjaan.bloghub::lang.components.author.filter_comment',
            'type'          => 'string',
            'default'       => '{{ :slug }}',
        ];
        $properties['archiveAuthor'] = [
            'title'         => 'spanjaan.bloghub::lang.components.archive_author.title',
            'description'   => 'spanjaan.bloghub::lang.components.archive_author.description',
            'type'          => 'string',
            'default'       => 'blog/author',
        ];
        $properties['authorUseSlugOnly'] = [
            'title'         => 'spanjaan.bloghub::lang.components.author_use_slug_only.title',
            'description'   => 'spanjaan.bloghub::lang.components.author_use_slug_only.description',
            'type'          => 'checkbox',
            'default'       => 1,
        ];
        $parentProperties = parent::defineProperties();

        return array_merge($properties, $parentProperties);
    }

    /**
     * Get available CMS Pages for Author Archive
     *
     * @return void
     */
    public function getArchiveAuthorOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    /**
    * Run Component
    *
    * @return mixed
    */
    public function onRun()
    {
        $this->author = $this->page['author'] = $this->loadAuthor();

        if (!$this->author) {
            return $this->controller->run('404');
        }

        return parent::onRun();
    }

    /**
         * List Posts
         *
         * @return mixed
         */
    protected function listPosts()
    {
        $author = $this->author->id;
        $category = $this->category ? $this->category->id : null;
        $categorySlug = $this->category ? $this->category->slug : null;

        /*
         * List all the posts, eager load their categories
         */
        $isPublished = !parent::checkEditor();

        $posts = Post::with(['categories', 'featured_images', 'spanjaan_bloghub_tags'])
            ->where('user_id', '=', $author)
            ->listFrontEnd([
                'page'             => $this->property('pageNumber'),
                'sort'             => $this->property('sortOrder'),
                'perPage'          => $this->property('postsPerPage'),
                'search'           => trim(input('search') ?? ''),
                'category'         => $category,
                'published'        => $isPublished,
                'exceptPost'       => is_array($this->property('exceptPost'))
                    ? $this->property('exceptPost')
                    : preg_split('/,\s*/', $this->property('exceptPost'), -1, PREG_SPLIT_NO_EMPTY),
                'exceptCategories' => is_array($this->property('exceptCategories'))
                    ? $this->property('exceptCategories')
                    : preg_split('/,\s*/', $this->property('exceptCategories'), -1, PREG_SPLIT_NO_EMPTY),
            ]);

        /*
         * Add a "url" helper attribute for linking to each post and category
         */

        $posts->each(function ($post) use ($categorySlug) {
            $post->setUrl($this->postPage, $this->controller, ['category' => $categorySlug]);

            $post->categories->each(function ($category) {
                $category->setUrl($this->categoryPage, $this->controller);
            });
        });

        return $posts;
    }

    /**
    * Load Author.
    *
    * @return BackendUser|null
    */
    protected function loadAuthor()
    {
        if (!$slug = $this->property('authorFilter')) {
            return null;
        }

        $user = BackendUser::where('spanjaan_bloghub_author_slug', $slug)->first();

        if (!$user) {
            return null;
        }

        if ($this->property('authorUseSlugOnly') == false && $user->login != $slug) {
            return null;
        }

        return $user;
    }

}

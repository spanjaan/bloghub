<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Behaviors;

use Cms\Classes\Controller;
use Winter\Storm\Extension\ExtensionBase;
use Winter\Blog\Models\Post;
use Spanjaan\BlogHub\Classes\BlogHubPost;
use Spanjaan\BlogHub\Models\Comment;
use Spanjaan\BlogHub\Models\Tag;

class BlogHubPostModel extends ExtensionBase
{
    /**
     * Parent Post Model
     *
     * @var Post
     */
    protected Post $model;

    /**
     * BlogHub Post Model DataSet
     *
     * @var ?BlogHubPost
     */
    protected ?BlogHubPost $bloghubSet;

    /**
     * Constructor
     *
     * @param Post $model
     */
    public function __construct(Post $model)
    {
        $this->model = $model;

        // Add Blog Comments
        $model->hasMany['spanjaan_bloghub_comments'] = [
            Comment::class
        ];

        $model->hasMany['spanjaan_bloghub_comments_count'] = [
            Comment::class,
            'count' => true
        ];

        // Add Blog Tags
        $model->belongsToMany['spanjaan_bloghub_tags'] = [
            Tag::class,
            'table' => 'spanjaan_bloghub_tags_posts',
            'order' => 'slug'
        ];

        // Register Tags Scope
        $model->addDynamicMethod('scopeFilterTags', function ($query, $tags) {
            return $query->whereHas('spanjaan_bloghub_tags', function ($q) use ($tags) {
                $q->withoutGlobalScope(NestedTreeScope::class)->whereIn('id', $tags);
            });
        });

        // Register Deprecated Methods
        $model->bindEvent('model.afterFetch', fn () => $this->registerDeprecatedMethods($model));
    }

    /**
     * Register deprecated methods
     *
     * @param Post $model
     * @return void
     */
    protected function registerDeprecatedMethods(Post $model)
    {
        $bloghub = $this->getBloghubAttribute();

        // Dynamic Method - Receive Similar Posts from current Model
        $model->addDynamicMethod(
            'bloghub_similar_posts',
            fn ($limit = 3, $exclude = null) => $bloghub->getRelated($limit, $exclude)
        );

        // Dynamic Method - Receive Random Posts from current Model
        $model->addDynamicMethod(
            'bloghub_random_posts',
            fn ($limit = 3, $exclude = null) => $bloghub->getRandom($limit, $exclude)
        );

        // Dynamic Method - Get Next Post in the same category
        $model->addDynamicMethod(
            'bloghub_next_post_in_category',
            fn () => $bloghub->getNext(1, true)
        );

        // Dynamic Method - Get Previous Post in the same category
        $model->addDynamicMethod(
            'bloghub_prev_post_in_category',
            fn () => $bloghub->getPrevious(1, true)
        );

        // Dynamic Method - Get Next Post
        $model->addDynamicMethod(
            'bloghub_next_post',
            fn () => $bloghub->getNext()
        );

        // Dynamic Method - Get Previous Post
        $model->addDynamicMethod(
            'bloghub_prev_post',
            fn () => $bloghub->getPrevious()
        );
    }

    /**
     * Get main BlogHub Space
     *
     * @return BlogHubPost
     */
    public function getBloghubAttribute()
    {
        if (empty($this->bloghubSet)) {
            $this->bloghubSet = new BlogHubPost($this->model);
        }
        return $this->bloghubSet;
    }

    /**
     * After Fetch Hook
     *
     * @return void
     */
    protected function afterFetch()
    {
        $tags = $this->model->spanjaan_bloghub_tags;
        if ($tags->count() === 0) {
            return;
        }

        /** @var Controller|null */
        $ctrl = Controller::getController();
        if ($ctrl instanceof Controller && !empty($ctrl->getLayout())) {
            $viewBag = $ctrl->getLayout()->getViewBag()->getProperties();

            // Set Tag URL
            if (isset($viewBag['bloghubTagPage'])) {
                $tags->each(fn ($tag) => $tag->setUrl($viewBag['bloghubTagPage'], $ctrl));
            }
        }
    }

}

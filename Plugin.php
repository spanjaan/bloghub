<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub;

use Backend;
use Event;
use Exception;
use Backend\Controllers\Users as BackendUsers;
use Backend\Facades\BackendAuth;
use Backend\Models\User as BackendUser;
use Backend\Widgets\Lists;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use Winter\Blog\Controllers\Posts;
use Winter\Blog\Models\Post;
use Spanjaan\BlogHub\Behaviors\BlogHubBackendUserModel;
use Spanjaan\BlogHub\Behaviors\BlogHubPostModel;
use Spanjaan\BlogHub\Models\Comment;
use Spanjaan\BlogHub\Models\Visitor;
use Symfony\Component\Yaml\Yaml;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /**
     * Required Extensions
     *
     * @var array
     */
    public $require = [
        'Winter.Blog'
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'spanjaan.bloghub::lang.plugin.name',
            'description' => 'spanjaan.bloghub::lang.plugin.description',
            'author'      => 'spanjaan <spanjaan@gmail.com>',
            'icon'        => 'icon-tags',
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

        // Extend available sorting options
        Post::$allowedSortingOptions['spanjaan_bloghub_views asc'] = 'spanjaan.bloghub::lang.sorting.bloghub_views_asc';
        Post::$allowedSortingOptions['spanjaan_bloghub_views desc'] = 'spanjaan.bloghub::lang.sorting.bloghub_views_desc';
        Post::$allowedSortingOptions['spanjaan_bloghub_unique_views asc'] = 'spanjaan.bloghub::lang.sorting.bloghub_unique_views_asc';
        Post::$allowedSortingOptions['spanjaan_bloghub_unique_views desc'] = 'spanjaan.bloghub::lang.sorting.bloghub_unique_views_desc';
        Post::$allowedSortingOptions['spanjaan_bloghub_comments_count asc'] = 'spanjaan.bloghub::lang.sorting.bloghub_comments_count_asc';
        Post::$allowedSortingOptions['spanjaan_bloghub_comments_count desc'] = 'spanjaan.bloghub::lang.sorting.bloghub_comments_count_desc';
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        // Add side menuts to Winter.Blog
        Event::listen('backend.menu.extendItems', function ($manager) {
            $manager->addSideMenuItems('Winter.Blog', 'blog', [
                'spanjaan_bloghub_tags' => [
                    'label'         => 'spanjaan.bloghub::lang.model.tags.label',
                    'icon'          => 'icon-tags',
                    'code'          => 'spanjaan-bloghub-tags',
                    'owner'         => 'spanjaan.BlogHub',
                    'url'           => Backend::url('spanjaan/bloghub/tags'),
                    'permissions'   => [
                        'spanjaan.bloghub.tags'
                    ]
                ],

                'spanjaan_bloghub_comments' => [
                    'label'         => 'spanjaan.bloghub::lang.model.comments.label',
                    'icon'          => 'icon-comments-o',
                    'code'          => 'spanjaan-bloghub-comments',
                    'owner'         => 'Spanjaan.BlogHub',
                    'url'           => Backend::url('spanjaan/bloghub/comments'),
                    'counter'       => Comment::where('status', 'pending')->count(),
                    'permissions'   => [
                        'spanjaan.bloghub.comments'
                    ]
                ]
            ]);
        });

        // Collect (Unique) Views
        Event::listen('cms.page.end', function (Controller $ctrl) {
            $pageObject = $ctrl->getPageObject();
            if (property_exists($pageObject, 'vars')) {
                $post = $pageObject->vars['post'] ?? null;
            } elseif (property_exists($pageObject, 'controller')) {
                $post = $pageObject->controller->vars['post'] ?? null;
            } else {
                $post = null;
            }
            if (empty($post)) {
                return;
            }

            $guest = BackendAuth::getUser() === null;
            $visitor = Visitor::currentUser();
            if (!$visitor->hasSeen($post)) {
                if ($guest) {
                    $post->spanjaan_bloghub_unique_views = is_numeric($post->spanjaan_bloghub_unique_views) ? $post->spanjaan_bloghub_unique_views+1 : 1;
                }
                $visitor->markAsSeen($post);
            }

            if ($guest) {
                $post->spanjaan_bloghub_views = is_numeric($post->spanjaan_bloghub_views) ? $post->spanjaan_bloghub_views+1 : 1;

                if (!empty($post->url)) {
                    $url = $post->url;
                    unset($post->url);
                }

                $post->save();

                if (isset($url)) {
                    $post->url = $url;
                }
            }
        });

        // Implement custom Models
        Post::extend(function ($model) {
            $model->implement[] = BlogHubPostModel::class;
        });

        BackendUser::extend(function ($model) {
            $model->implement[] = BlogHubBackendUserModel::class;
        });



        // Extend Form Fields on Posts Controller
        Posts::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof Post) {
                return;
            }

            // Add Comments Field
            $form->addSecondaryTabFields([
                'spanjaan_bloghub_comment_visible' => [
                    'tab'           => 'spanjaan.bloghub::lang.model.comments.label',
                    'type'          => 'switch',
                    'label'         => 'spanjaan.bloghub::lang.model.comments.post_visibility.label',
                    'comment'       => 'spanjaan.bloghub::lang.model.comments.post_visibility.comment',
                    'span'          => 'left',
                    'permissions'   => ['spanjaan.bloghub.comments.access_comments_settings']
                ],
                'spanjaan_bloghub_comment_mode' => [
                    'tab'           => 'spanjaan.bloghub::lang.model.comments.label',
                    'type'          => 'dropdown',
                    'label'         => 'spanjaan.bloghub::lang.model.comments.post_mode.label',
                    'comment'       => 'spanjaan.bloghub::lang.model.comments.post_mode.comment',
                    'showSearch'    => false,
                    'span'          => 'left',
                    'options'       => [
                        'open' => 'spanjaan.bloghub::lang.model.comments.post_mode.open',
                        'restricted' => 'spanjaan.bloghub::lang.model.comments.post_mode.restricted',
                        'private' => 'spanjaan.bloghub::lang.model.comments.post_mode.private',
                        'closed' => 'spanjaan.bloghub::lang.model.comments.post_mode.closed',
                    ],
                    'permissions'   => ['spanjaan.bloghub.comments.access_comments_settings']
                ],
            ]);
            // Add Tags Field
            $form->addSecondaryTabFields([
                'spanjaan_bloghub_tags' => [
                    'label'         => 'spanjaan.bloghub::lang.model.tags.label',
                    'mode'          => 'relation',
                    'tab'           => 'winter.blog::lang.post.tab_categories',
                    'type'          => 'taglist',
                    'nameFrom'      => 'slug',
                    'permissions'   => ['spanjaan.bloghub.tags']
                ]
            ]);

        });

        // Extend List Columns on Posts Controller
        Posts::extendListColumns(function (Lists $list, $model) {
            if (!$model instanceof Post) {
                return;
            }

            $list->addColumns([
                'spanjaan_bloghub_views' => [
                    'label' => 'spanjaan.bloghub::lang.model.visitors.views',
                    'type' => 'number',
                    'select' => 'concat(winter_blog_posts.spanjaan_bloghub_views, " / ", winter_blog_posts.spanjaan_bloghub_unique_views)',
                    'align' => 'left'
                ]
            ]);
        });

        // Add Posts Filter Scope
        Posts::extendListFilterScopes(function ($filter) {
            $filter->addScopes([
                'spanjaan_bloghub_tags' => [
                    'label' => 'spanjaan.bloghub::lang.model.tags.label',
                    'modelClass' => 'Spanjaan\BlogHub\Models\Tag',
                    'nameFrom' => 'slug',
                    'scope' => 'FilterTags'
                ]
            ]);
        });

        // Extend Backend Users Controller
        BackendUsers::extendFormFields(function ($form, $model, $context) {
            if (!$model instanceof BackendUser) {
                return;
            }

            // Add Display Name
            $form->addTabFields([
                'spanjaan_bloghub_display_name' => [
                    'label'         => 'spanjaan.bloghub::lang.model.users.displayName',
                    'description'   => 'spanjaan.bloghub::lang.model.users.displayNameDescription',
                    'tab'           => 'backend::lang.user.account',
                    'type'          => 'text',
                    'span'          => 'left'
                ],
                'spanjaan_bloghub_author_slug' => [
                    'label'         => 'spanjaan.bloghub::lang.model.users.authorSlug',
                    'description'   => 'spanjaan.bloghub::lang.model.users.authorSlugDescription',
                    'tab'           => 'backend::lang.user.account',
                    'type'          => 'text',
                    'span'          => 'right'
                ],
                'spanjaan_bloghub_about_me' => [
                    'label'         => 'spanjaan.bloghub::lang.model.users.aboutMe',
                    'description'   => 'spanjaan.bloghub::lang.model.users.aboutMeDescription',
                    'tab'           => 'backend::lang.user.account',
                    'type'          => 'textarea',
                ]
            ]);
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            \Spanjaan\BlogHub\Components\PostsByAuthor::class => 'bloghubPostsByAuthor',
            \Spanjaan\BlogHub\Components\PostsByCommentCount::class => 'bloghubPostsByCommentCount',
            \Spanjaan\BlogHub\Components\PostsByDate::class => 'bloghubPostsByDate',
            \Spanjaan\BlogHub\Components\PostsByTag::class => 'bloghubPostsByTag',
            \Spanjaan\BlogHub\Components\CommentList::class => 'bloghubCommentList',
            \Spanjaan\BlogHub\Components\CommentSection::class => 'bloghubCommentSection',
            \Spanjaan\BlogHub\Components\Tags::class => 'bloghubTags',
            \Spanjaan\BlogHub\Components\PopularPosts::class => 'popularPosts',
            \Spanjaan\BlogHub\Components\AuthorInfo::class => 'authorInfo',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'spanjaan.bloghub.comments' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.access_comments',
                'comment' => 'spanjaan.bloghub::lang.permissions.access_comments_comment',
            ],
            'spanjaan.bloghub.comments.access_comments_settings' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.manage_post_settings'
            ],
            'spanjaan.bloghub.comments.moderate_comments' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.moderate_comments'
            ],
            'spanjaan.bloghub.comments.delete_comments' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.delete_commpents'
            ],
            'spanjaan.bloghub.tags' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.access_tags',
                'comment' => 'spanjaan.bloghub::lang.permissions.access_tags_comment',
            ],
            'spanjaan.bloghub.tags.promoted' => [
                'tab'   => 'winter.blog::lang.blog.tab',
                'label' => 'spanjaan.bloghub::lang.permissions.promote_tags'
            ]
        ];
    }

    /**
     * Registers backend navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [];
    }

    /**
     * Registers settings navigation items for this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'spanjaan_bloghub_config' => [
                'label'         => 'spanjaan.bloghub::lang.settings.config.label',
                'description'   => 'spanjaan.bloghub::lang.settings.config.description',
                'category'      => 'winter.blog::lang.blog.menu_label',
                'icon'          => 'icon-pencil-square-o',
                'class'         => 'Spanjaan\BlogHub\Models\BlogHubSettings',
                'order'         => 500,
                'keywords'      => 'blog post meta data',
                'permissions'   => ['winter.blog.manage_settings'],
                'size'          => 'adaptive'
            ]
        ];
    }

    /**
     * Registers any report widgets provided by this package.
     *
     * @return array
     */
    public function registerReportWidgets()
    {
        return [
            \Spanjaan\BlogHub\ReportWidgets\CommentsList::class => [
                'label' => 'spanjaan.bloghub::lang.widgets.comments_list.label',
                'context' => 'dashboard',
                'permission' => [
                    'winter.blog.access_other_posts',
                    'spanjaan.bloghub.comments'
                ]
            ],
            \Spanjaan\BlogHub\ReportWidgets\PostsList::class => [
                'label' => 'spanjaan.bloghub::lang.widgets.posts_list.label',
                'context' => 'dashboard',
                'permission' => [
                    'winter.blog.access_other_posts'
                ]
            ],
            \Spanjaan\BlogHub\ReportWidgets\PostsStatistics::class => [
                'label' => 'spanjaan.bloghub::lang.widgets.posts_statistics.label',
                'context' => 'dashboard',
                'permission' => [
                    'winter.blog.access_other_posts'
                ]
            ],
        ];
    }

}

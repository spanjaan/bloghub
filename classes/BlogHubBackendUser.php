<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Classes;

use Backend\Models\User;
use Cms\Classes\Controller;
use Winter\Blog\Models\Post;
use Winter\Storm\Support\Traits\Singleton;

class BlogHubBackendUser
{
    use Singleton;

    /**
     * User Model
     *
     * @var User
     */
    protected User $model;

    /**
     * Create a new BlogPost
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Call dynamic property method.
     *
     * @param string $method
     * @param array|null $arguments
     * @return mixed
     */
    public function __call(string $method, ?array $arguments = [])
    {
        $methodName = 'get' . ucwords(str_replace('_', '', $method));

        if (method_exists($this, $methodName)) {
            return $this->{$methodName}(...$arguments);
        }

        return null;
    }

    /**
     * Get current CMS controller.
     *
     * @return Controller|null
     */
    protected function getController(): ?Controller
    {
        return Controller::getController();
    }

    /**
     * Return author archive page URL.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        $controller = $this->getController();
        if ($controller instanceof Controller && !empty($controller->getLayout())) {
            return url('author/' . $this->getSlug());
        }

        return null;
    }

    /**
     * Return author slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->model->spanjaan_bloghub_author_slug ?: $this->model->login;
    }

    /**
     * Return author display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if (!empty($this->model->spanjaan_bloghub_display_name)) {
            return $this->model->spanjaan_bloghub_display_name;
        }

        $name = trim($this->model->first_name . ' ' . $this->model->last_name);
        return $name ?: ucfirst($this->model->login);
    }

    /**
     * Return author display name (alias).
     *
     * @return string
     */
    public function getDisplay(): string
    {
        return $this->getDisplayName();
    }

    /**
     * Return author about me text.
     *
     * @return string|null
     */
    public function getAboutMe(): ?string
    {
        return $this->model->spanjaan_bloghub_about_me;
    }

    /**
     * Return author about me text (alias).
     *
     * @return string|null
     */
    public function getAbout(): ?string
    {
        return $this->getAboutMe();
    }

    /**
    * Return author avatar image URL.
    *
    * @return string|null
    */
    public function getAvatar(): ?string
    {
        if (!empty($this->model->avatar)) {
            return $this->model->avatar->getPath();
        }

        return null;
    }


    /**
                     * Return author post count.
                     *
                     * @return int
                     */
    public function getCount(): int
    {
        return Post::where('user_id', $this->model->id)->count();
    }
}

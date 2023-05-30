<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Behaviors;

use Backend\Models\User;
use Winter\Storm\Extension\ExtensionBase;
use Spanjaan\BlogHub\Classes\BlogHubBackendUser;

class BlogHubBackendUserModel extends ExtensionBase
{
    /**
     * Parent Post Model
     *
     * @var User
     */
    protected User $model;

    /**
     * BlogHub Post Model DataSet
     *
     * @var ?BlogHubBackendUser
     */
    protected ?BlogHubBackendUser $bloghubSet;

    /**
     * Get main BlogHub Space
     *
     * @return BlogHubBackendUser
     */
    public function getBloghubAttribute()
    {
        if (empty($this->bloghubSet)) {
            $this->bloghubSet = new BlogHubBackendUser($this->model);
        }
        return $this->bloghubSet;
    }

}

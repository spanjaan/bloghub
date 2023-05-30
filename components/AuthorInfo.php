<?php

namespace Spanjaan\BlogHub\Components;

use Cms\Classes\ComponentBase;
use Spanjaan\BlogHub\Classes\BlogHubBackendUser;

class AuthorInfo extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'AuthorInfo Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        // Get the current post
        $post = $this->page['post'];

        // Check if the post exists and retrieve the user
        if ($post && $post->user) {
            $author = new BlogHubBackendUser($post->user);

            // Pass the author information to the component's view
            $this->page['author'] = $author;
        }
    }


}

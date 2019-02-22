<?php

namespace CS\User;

if (!defined('ABSPATH')) {
    exit;
}

class User extends \WP_User
{

    /**
     * Getters
     */
    public function get_email()
    {

        return $this->data->user_email;
    }

    public function get_username()
    {

        return $this->data->user_login;
    }

    public function get_display_name()
    {

        return $this->data->display_name;
    }

    public function get_date_registered()
    {

        return date('m/d/Y H:i', strtotime($this->data->user_registered));
    }

    public function get_website()
    {

        return $this->data->user_url;
    }
}

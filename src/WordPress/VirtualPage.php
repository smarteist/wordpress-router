<?php

namespace Hexbit\Router\WordPress;

use stdClass;
use Symfony\Component\HttpFoundation\Response;
use WP_Post;


class VirtualPage
{

    private $uri;
    private $title;
    private $template;
    private $templateDirectory = null;
    private $wpPost;

    public function __construct(string $template, string $title, string $templateDirectory = null)
    {
        $this->setTemplate($template);
        $this->setTitle($title);
        $this->setCustomTemplate($templateDirectory);
    }

    public function onRoute()
    {
        add_action('template_redirect', [$this, 'createPage']);
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    public function template($templateDir)
    {
        if (isset($this->templateDirectory) && is_file($this->templateDirectory)) {
            return $this->templateDirectory;
        }
        return $templateDir;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = filter_var($title, FILTER_SANITIZE_STRING);

        return $this;
    }

    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function setCustomTemplate($templateDirectory)
    {
        if (isset($templateDirectory)) {
            $this->templateDirectory = $templateDirectory;
            add_filter('page_template', [$this, 'template']);
        }
    }

    private function createPostInstance()
    {
        if (!isset($this->wpPost)) {
            $post = new stdClass();
            $post->ID = -99;
            $post->ancestors = array(); // 3.6
            $post->comment_status = 'closed';
            $post->comment_count = 0;
            $post->filter = 'raw';
            $post->guid = home_url($this->uri);
            $post->is_virtual = true;
            $post->menu_order = 0;
            $post->pinged = '';
            $post->ping_status = 'closed';
            $post->post_title = $this->title;
            $post->post_name = sanitize_title($this->template); // append random number to avoid clash
            $post->post_excerpt = '';
            $post->post_parent = 0;
            $post->post_type = 'page';
            $post->post_status = 'publish';
            $post->post_date = current_time('mysql');
            $post->post_date_gmt = current_time('mysql', 1);
            $post->modified = $post->post_date;
            $post->modified_gmt = $post->post_date_gmt;
            $post->post_password = '';
            $post->post_content_filtered = '';
            $post->post_author = is_user_logged_in() ? get_current_user_id() : 0;
            $post->post_content = '';
            $post->post_mime_type = '';
            $post->to_ping = '';
            $this->wpPost = new WP_Post($post);
        }
        return $this->wpPost;
    }

    public function createPage()
    {
        $this->createPostInstance();
        global $wp, $wp_query;

        // Update the main query
        $wp_query->current_post = $this->wpPost->ID;
        $wp_query->found_posts = 1;
        $wp_query->is_page = true;//important part
        $wp_query->is_singular = true;//important part
        $wp_query->is_single = false;
        $wp_query->is_attachment = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        $wp_query->is_tag = false;
        $wp_query->is_tax = false;
        $wp_query->is_author = false;
        $wp_query->is_date = false;
        $wp_query->is_year = false;
        $wp_query->is_month = false;
        $wp_query->is_day = false;
        $wp_query->is_time = false;
        $wp_query->is_search = false;
        $wp_query->is_feed = false;
        $wp_query->is_comment_feed = false;
        $wp_query->is_trackback = false;
        $wp_query->is_home = false;
        $wp_query->is_embed = false;
        $wp_query->is_404 = false;
        $wp_query->is_paged = false;
        $wp_query->is_admin = false;
        $wp_query->is_preview = false;
        $wp_query->is_robots = false;
        $wp_query->is_posts_page = false;
        $wp_query->is_post_type_archive = false;
        $wp_query->max_num_pages = 1;
        $wp_query->post = $this->wpPost;
        $wp_query->posts = array($this->wpPost);
        $wp_query->post_count = 1;
        $wp_query->queried_object = $this->wpPost;
        $wp_query->queried_object_id = $this->wpPost->ID;
        $wp_query->query_vars['error'] = '';
        unset($wp_query->query['error']);

        $GLOBALS['wp_query'] = $wp_query;

        $wp->query = array();
        $wp->register_globals();
        wp_cache_add(-99, $this->wpPost, 'posts');
        //set 200 header
        @status_header(200);
    }
}

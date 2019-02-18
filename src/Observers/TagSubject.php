<?php

namespace Bonnier\WP\Redirect\Observers;

class TagSubject extends AbstractSubject
{
    /** @var \WP_Term */
    private $tag;

    public function __construct()
    {
        parent::__construct();
        add_action('created_post_tag', [$this, 'updateTag']);
        add_action('edited_post_tag', [$this, 'updateTag']);
    }

    public function getTag(): ?\WP_Term
    {
        return $this->tag;
    }

    public function updateTag(int $termID)
    {
        if ((($tag = get_term($termID)) && $tag instanceof \WP_Term) && $tag->taxonomy === 'post_tag') {
            $this->tag = $tag;
            $this->notify();
        }
    }
}

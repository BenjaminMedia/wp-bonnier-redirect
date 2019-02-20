<?php

namespace Bonnier\WP\Redirect\Observers;

class CategorySubject extends AbstractSubject
{
    /** @var \WP_Term */
    private $category;

    public function __construct()
    {
        parent::__construct();
        add_action('create_category', [$this, 'updateCategory']);
        add_action('edited_category', [$this, 'updateCategory']);
    }

    public function getCategory(): ?\WP_Term
    {
        return $this->category;
    }

    public function setCategory(\WP_Term $category): CategorySubject
    {
        $this->category = $category;

        return $this;
    }

    public function updateCategory(int $termID)
    {
        if (($category = get_term($termID)) && $category instanceof \WP_Term && $category->taxonomy === 'category') {
            $this->category = $category;
            $this->notify();
        }
    }

}

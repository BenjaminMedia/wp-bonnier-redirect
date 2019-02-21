<?php

namespace Bonnier\WP\Redirect\Observers;

class CategorySubject extends AbstractSubject
{
    const UPDATE = 'update';
    const DELETE = 'delete';

    /** @var \WP_Term|null */
    private $category;

    /** @var string */
    private $type;

    private $affectedPosts = [];

    public function __construct()
    {
        parent::__construct();
        add_action('create_category', [$this, 'updateCategory']);
        add_action('edited_category', [$this, 'updateCategory']);
        add_action('delete_category', [$this, 'deletedCategory'], 10, 4);
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

    public function getAffectedPosts(): array
    {
        return $this->affectedPosts;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function updateCategory(int $termID)
    {
        if (($category = get_term($termID)) && $category instanceof \WP_Term && $category->taxonomy === 'category') {
            $this->category = $category;
            $this->type = self::UPDATE;
            $this->notify();
        }
    }

    public function update(\WP_Term $category)
    {
        $this->type = self::UPDATE;
        $this->category = $category;
        $this->notify();
    }

    public function deletedCategory(int $termID, string $taxonomy, \WP_Term $category, array $objectIds)
    {
        $this->affectedPosts = $objectIds;
        $this->category = $category;
        $this->type = self::DELETE;
        $this->notify();
    }
}

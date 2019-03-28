<?php

namespace Bonnier\WP\Redirect\Observers;

class TagSubject extends AbstractSubject
{
    const UPDATE = 'update';
    const DELETE = 'delete';

    /** @var \WP_Term */
    private $tag;

    /** @var string */
    private $type;

    public function __construct()
    {
        parent::__construct();
        add_action('create_post_tag', [$this, 'updateTag']);
        add_action('edited_post_tag', [$this, 'updateTag']);
        add_action('delete_post_tag', [$this, 'deleteTag'], 10, 3);
    }

    /**
     * @return \WP_Term|null
     */
    public function getTag(): ?\WP_Term
    {
        return $this->tag;
    }

    public function setTag(\WP_Term $tag): TagSubject
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return TagSubject
     */
    public function setType(string $type): TagSubject
    {
        if (in_array($type, [self::UPDATE, self::DELETE])) {
            $this->type = $type;
        }
        return $this;
    }

    /**
     * @param int $termID
     */
    public function updateTag(int $termID)
    {
        if ((($tag = get_term($termID)) && $tag instanceof \WP_Term) && $tag->taxonomy === 'post_tag') {
            $this->tag = $tag;
            $this->type = self::UPDATE;
            $this->notify();
        }
    }

    /**
     * @param int $termID
     * @param string $taxonomy
     * @param \WP_Term $tag
     */
    public function deleteTag(int $termID, string $taxonomy, \WP_Term $tag)
    {
        $this->tag = $tag;
        $this->type = self::DELETE;
        $this->notify();
    }
}

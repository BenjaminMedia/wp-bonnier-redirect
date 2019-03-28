<?php

namespace Bonnier\WP\Redirect\Observers\Interfaces;

interface ObserverInterface
{
    public function update(SubjectInterface $subject);
}

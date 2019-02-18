<?php

namespace Bonnier\WP\Redirect\Observers\Interfaces;

interface SubjectInterface
{
    public function attach(ObserverInterface $observer);
    public function detach(ObserverInterface $observer);
    public function notify();
}

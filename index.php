<?php
require_once 'vendor/autoload.php';
ini_set('assert.exception', 1);

use Rkliver\TaskForce\logic\AvailableActions;

$strategy = new AvailableActions(AvailableActions::STATUS_NEW, 3, 1);

var_dump('new -> performer', $strategy->getAvailableActions(AvailableActions::ROLE_PERFORMER, 2));
var_dump('new -> client,alien', $strategy->getAvailableActions(AvailableActions::ROLE_CLIENT, 2));
var_dump('new -> client,same', $strategy->getAvailableActions(AvailableActions::ROLE_CLIENT, 1));

var_dump('proceed -> performer,same', $strategy->getAvailableActions(AvailableActions::ROLE_PERFORMER, 3));

assert($strategy->getNextStatus(AvailableActions::ACTION_CANCEL) == AvailableActions::STATUS_CANCEL, 'cancel action');
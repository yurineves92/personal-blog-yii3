<?php

declare(strict_types=1);

use App\Auth\Rbac\RbacItemsStorage;
use App\Auth\Rbac\UserRoleAssignmentsStorage;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Rbac\AssignmentsStorageInterface;
use Yiisoft\Rbac\ItemsStorageInterface;
use Yiisoft\Rbac\ManagerInterface;

// `ManagerInterface => Manager` já vem da configuração do pacote yiisoft/rbac.
return [
    ItemsStorageInterface::class => RbacItemsStorage::class,
    AssignmentsStorageInterface::class => UserRoleAssignmentsStorage::class,
    AccessCheckerInterface::class => ManagerInterface::class,
];

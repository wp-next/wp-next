<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\AddTagToChangelogReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushNextDevReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetNextMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateBranchAliasReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::DATA_TO_REMOVE, [
        'require' => [
            // remove these to merge of packages' composer.json
            'tracy/tracy' => '*',
            'phpunit/phpunit' => '*',
        ],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ]);

    /*
    $parameters->set(Option::DIRECTORIES_TO_REPOSITORIES, [
        __DIR__.'/packages/Ajax' => 'git@github.com:wp-next/ajax.git',
        __DIR__.'/packages/Console' => 'git@github.com:wp-next/console.git',
        __DIR__.'/packages/Core' => 'git@github.com:wp-next/core.git',
        __DIR__.'/packages/Hook' => 'git@github.com:wp-next/hook.git',
        __DIR__.'/packages/Routing' => 'git@github.com:wp-next/routing.git',
        __DIR__.'/packages/Support' => 'git@github.com:wp-next/support.git',
        __DIR__.'/packages/View' => 'git@github.com:wp-next/view.git',
    ]);
    */

    $services = $containerConfigurator->services();

    // release workers - in order to execute
    $services->set(UpdateReplaceReleaseWorker::class);
    $services->set(SetCurrentMutualDependenciesReleaseWorker::class);
    $services->set(AddTagToChangelogReleaseWorker::class);
    $services->set(TagVersionReleaseWorker::class);
    $services->set(PushTagReleaseWorker::class);
    $services->set(SetNextMutualDependenciesReleaseWorker::class);
    $services->set(UpdateBranchAliasReleaseWorker::class);
    $services->set(PushNextDevReleaseWorker::class);
};

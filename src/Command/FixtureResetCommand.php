<?php

namespace Acquia\Orca\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Provides the "fixture:reset" command.
 */
class FixtureResetCommand extends CommandBase {

  /**
   * The collection to return.
   *
   * @var \Robo\Collection\CollectionBuilder
   */
  private $collection;

  /**
   * Resets the test fixture to its base state and optionally reinstalls Drupal.
   *
   * @command fixture:reset
   * @option code Reset the codebase. All uncommitted code changes will be
   *   lost! Committed code changes will be saved to a timestamped backup
   *   branch.
   * @option db Empty the Drupal database.
   * @option drupal Reinstall Drupal.
   * @option all Reset the code, empty the database, and reinstall Drupal. Takes
   *   precedence over other options.
   * @aliases reset
   *
   * @return \Robo\Collection\CollectionBuilder|int
   */
  public function execute(array $options = [
    'code' => FALSE,
    'db' => FALSE,
    'drupal' => FALSE,
    'all' => FALSE,
  ]) {
    $this->assert(file_exists($this->buildPath()), 'No fixture present.');

    if (!in_array(TRUE, [
      $options['all'],
      $options['code'],
      $options['db'],
      $options['drupal'],
    ])) {
      $this->say('Nothing to do. Use a command option. See `orca help fixture:reset`.');
      return Result::EXITCODE_ERROR;
    }

    $confirm = $this->confirm('Are you sure you want to reset the test fixture?');
    if (!$confirm && !$options['no-interaction']) {
      return Result::EXITCODE_USER_CANCEL;
    }

    $this->collection = $this->collectionBuilder();

    if ($options['all'] || $options['code']) {
      $this->resetAndBackupCode();
    }

    // Reinstalling Drupal already drops all tables in the database, so there's
    // never a reason to take both actions discretely.
    if ($options['all'] || $options['drupal']) {
      $this->collection->addTask($this->installDrupal());
    }
    elseif ($options['db']) {
      $this->collection->addTask($this->emptyDrupalDatabase());
    }

    return $this->collection;
  }

  /**
   * Resets and backs up the build code.
   */
  protected function resetAndBackupCode() {
    $git = $this->taskGitStack()
      ->dir($this->buildPath());
    $this->collection
      ->addTaskList([
        $git->exec(sprintf('branch backup-%s', date('Y-m-d-Gis'))),
        $this->fixFilePermissions(),
        $git->exec(sprintf('reset --hard %s', self::BASE_FIXTURE_BRANCH)),
        $git->exec('clean -fd'),
      ]);
  }

}

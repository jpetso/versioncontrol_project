<?php
// $Id$
/**
 * @file
 * Version Control / Project Node Integration - Integrates project nodes
 * (provided by the Project module) with version control systems supported
 * by the Version Control API.
 *
 * This file integrates versioncontrol_project with the metrics module,
 * providing node metrics based on VCS data.
 *
 * Copyright 2007 by Peter Cawley ("corsix", http://drupal.org/user/210422)
 * Copyright 2008 by Jakob Petsovits ("jpetso", http://drupal.org/user/56020)
 */

function versioncontrol_project_metrics_functions() {
  return array(
    'versioncontrol_project_lines_changed',
  );
}

function versioncontrol_project_lines_changed($op, $options = NULL, $node = NULL) {
  switch ($op) {
    case 'info':
      return array(
        'name' => t('Version Control: Total lines changed'),
        'description' => t("Returns the number of lines that were changed in the project's VCS commits in the last week"),
      );

    case 'compute':
      // Before doing anything, check if we can return commit statistics at all.
      if (isset($node->versioncontrol_project)) {
        $repo_id = $node->versioncontrol_project['repo_id'];
      }
      if (!isset($repo_id) || $repo_id == 0)) {
        $error_message = t('Version control support is not enabled for this node.');
      }
      else {
        $repository = versioncontrol_get_repository($repo_id);
        if (!isset($repository)) {
          $error_message = t('Repository with repository id !id not found.',
                              array('!id' => $repo_id));
        }
      }

      if (isset($error_message)) {
        return array(
          'value' => 0,
          'description' => $error_message,
        );
      }

      // Ok, we can do it... let's get those line counts.
      $constraints = array(
        'date_lower' => time() - (60 * 60 * 24 * 7),
      );
      $constraints = versioncontrol_project_get_operation_constraints(
        $constraints, array('nids' => array($node->nid))
      );
      $commit_operations = versioncontrol_get_commit_operations($constraints);
      $count = 0;
      foreach ($commit_operations as $key => $commit_operation) {
        $operation_items = versioncontrol_get_operation_items($commit_operation, TRUE);
        foreach ($operation_items as $path => $item) {
          $count += $item['line_changes']['added'];
          $count += $item['line_changes']['removed'];
        }
      }

      return array(
        'value' => $count,
        'description' => format_plural($count, '1 line of code changed.', '@count lines of code changed.'),
      );

    case 'options':
      return array();
  }
}

<?php
// $Id$
/**
 * @file
 * Integrates versioncontrol_project with the metrics module, providing node metrics based
 * upon VCS data.
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
      $constraints = array(
        'date_lower' => time() - (60 * 60 * 24 * 7),
      );
      $constraints = versioncontrol_project_get_commit_constraints($constraints, array('nids' => array($node->nid)));
      $commits = versioncontrol_get_commits($constraints);
      $count = 0;
      foreach ($commits as $key => $commit) {
        $statistics = versioncontrol_get_commit_statistics($commit);
        $count += $statistics['lines_added'];
        $count += $statistics['lines_removed'];
      }
      
      return array(
        'value' => $count, 
        'description' => format_plural($count, '1 line of code changed.', '@count lines of code changed.'),
      );

    case 'options':
      return array();
  }
}

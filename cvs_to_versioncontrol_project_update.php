<?php
// $Id$

/**
 * @file
 * Administrative page for handling updates from cvs module to
 * versioncontrol_project module.
 *
 * Place this file in the root of your Drupal installation (ie, the same
 * directory as index.php), point your browser to
 * "http://yoursite/cvs_to_versioncontrol_project_update.php" and follow the
 * instructions.
 *
 * If you are not logged in as administrator, you will need to modify the access
 * check statement below. Change the TRUE to a FALSE to disable the access
 * check. After finishing the upgrade, be sure to open this file and change the
 * FALSE back to a TRUE!
 */

// Enforce access checking?
$access_check = TRUE;

/**
 * Convert project commit access data.
 */
function cvs_to_versioncontrol_project_update_1() {

  // This determines how many users will be processed in each batch run. A reasonable
  // default has been chosen, but you may want to tweak depending on your setup.
  $limit = 100;

  // Multi-part update
  if (!isset($_SESSION['cvs_to_versioncontrol_project_update_1'])) {
    $_SESSION['cvs_to_versioncontrol_project_update_1'] = 0;
    $_SESSION['cvs_to_versioncontrol_project_update_1_max'] = db_result(db_query("SELECT COUNT(*) FROM {cvs_project_maintainers}"));
  }

  // Pull the next batch of users.
  $comaintainers = db_query_range("SELECT nid, uid FROM {cvs_project_maintainers} ORDER BY nid, uid", $_SESSION['cvs_to_versioncontrol_project_update_1'], $limit);

  // Loop through each co-maintainer.
  while ($comaintainer = db_fetch_object($comaintainers)) {
    db_query("INSERT INTO {versioncontrol_project_comaintainers} (nid, uid) VALUES (%d, %d)", $comaintainer->nid, $comaintainer->uid);
    $_SESSION['cvs_to_versioncontrol_project_update_1']++;
  }

  if ($_SESSION['cvs_to_versioncontrol_project_update_1'] >= $_SESSION['cvs_to_versioncontrol_project_update_1_max']) {
    $count = $_SESSION['cvs_to_versioncontrol_project_update_1_max'];
    unset($_SESSION['cvs_to_versioncontrol_project_update_1']);
    unset($_SESSION['cvs_to_versioncontrol_project_update_1_max']);
    return array(array('success' => TRUE, 'query' => t('Converted @count project co-maintainer entries.', array('@count' => $count))));
  }
  return array('#finished' => $_SESSION['cvs_to_versioncontrol_project_update_1'] / $_SESSION['cvs_to_versioncontrol_project_update_1_max']);
}

/**
 * Convert project repository data.
 */
function cvs_to_versioncontrol_project_update_2() {

  // This determines how many projects will be processed in each batch run. A reasonable
  // default has been chosen, but you may want to tweak depending on your setup.
  $limit = 100;

  // Multi-part update
  if (!isset($_SESSION['cvs_to_versioncontrol_project_update_2'])) {
    $_SESSION['cvs_to_versioncontrol_project_update_2'] = 0;
    $_SESSION['cvs_to_versioncontrol_project_update_2_max'] = db_result(db_query("SELECT COUNT(*) FROM {cvs_projects}"));
  }

  // Pull the next batch of users.
  $projects = db_query_range("SELECT p.nid, p.rid, p.directory, r.modules FROM {cvs_projects} p INNER JOIN {cvs_repositories} r ON p.rid = r.rid ORDER BY p.nid", $_SESSION['cvs_to_versioncontrol_project_update_2'], $limit);

  // Loop through each project.
  while ($project = db_fetch_object($projects)) {
    // Add the repo module, and chop off the trailing slash.
    $directory = '/'. trim($project->modules) . drupal_substr($project->directory, 0, drupal_strlen($project->directory) - 1);
    db_query("INSERT INTO {versioncontrol_project_projects} (nid, repo_id, directory) VALUES (%d, %d, '%s')", $project->nid, $project->rid, $directory);
    $_SESSION['cvs_to_versioncontrol_project_update_2']++;
  }

  if ($_SESSION['cvs_to_versioncontrol_project_update_2'] >= $_SESSION['cvs_to_versioncontrol_project_update_2_max']) {
    $count = $_SESSION['cvs_to_versioncontrol_project_update_2_max'];
    unset($_SESSION['cvs_to_versioncontrol_project_update_2']);
    unset($_SESSION['cvs_to_versioncontrol_project_update_2_max']);
    return array(array('success' => TRUE, 'query' => t('Converted @count project repository entries.', array('@count' => $count))));
  }
  return array('#finished' => $_SESSION['cvs_to_versioncontrol_project_update_2'] / $_SESSION['cvs_to_versioncontrol_project_update_2_max']);
}

/**
 * Perform one update and store the results which will later be displayed on
 * the finished page.
 *
 * @param $module
 *   The module whose update will be run.
 * @param $number
 *   The update number to run.
 *
 * @return
 *   TRUE if the update was finished. Otherwise, FALSE.
 */
function update_data($module, $number) {

  $function = "cvs_to_versioncontrol_project_update_$number";
  $ret = $function();

  // Assume the update finished unless the update results indicate otherwise.
  $finished = 1;
  if (isset($ret['#finished'])) {
    $finished = $ret['#finished'];
    unset($ret['#finished']);
  }

  // Save the query and results for display by update_finished_page().
  if (!isset($_SESSION['update_results'])) {
    $_SESSION['update_results'] = array();
  }
  if (!isset($_SESSION['update_results'][$module])) {
    $_SESSION['update_results'][$module] = array();
  }
  if (!isset($_SESSION['update_results'][$module][$number])) {
    $_SESSION['update_results'][$module][$number] = array();
  }
  $_SESSION['update_results'][$module][$number] = array_merge($_SESSION['update_results'][$module][$number], $ret);

  return $finished;
}

function update_selection_page() {
  $output = '';
  $output .= '<p>Click Update to start the update process.</p>';

  drupal_set_title('CVS module to Version Control/Project Node integration module update');
  // Use custom update.js.
  drupal_add_js(update_js(), 'inline');
  $output .= drupal_get_form('update_script_selection_form');

  return $output;
}

function update_script_selection_form() {
  $form = array();

  $form['has_js'] = array(
    '#type' => 'hidden',
    '#default_value' => FALSE,
    '#attributes' => array('id' => 'edit-has_js'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Update',
  );
  return $form;
}

function update_update_page() {
  // Set the installed version so updates start at the correct place.
  $_SESSION['update_remaining'][] = array('module' => 'versioncontrol_project', 'version' => 1);
  $_SESSION['update_remaining'][] = array('module' => 'versioncontrol_project', 'version' => 2);

  // Keep track of total number of updates
  if (isset($_SESSION['update_remaining'])) {
    $_SESSION['update_total'] = count($_SESSION['update_remaining']);
  }

  if ($_POST['has_js']) {
    return update_progress_page();
  }
  else {
    return update_progress_page_nojs();
  }
}

function update_progress_page() {
  // Prevent browser from using cached drupal.js or update.js
  drupal_add_js('misc/progress.js', 'core', 'header', FALSE, TRUE);
  drupal_add_js(update_js(), 'inline');

  drupal_set_title('Updating');
  $output = '<div id="progress"></div>';
  $output .= '<p id="wait">Please wait while your site is being updated.</p>';
  return $output;
}

/**
 * Can't include misc/update.js, because it makes a direct call to update.php.
 *
 * @return unknown
 */
function update_js() {
  return "
  if (Drupal.jsEnabled) {
    $(document).ready(function() {
      $('#edit-has-js').each(function() { this.value = 1; });
      $('#progress').each(function () {
        var holder = this;

        // Success: redirect to the summary.
        var updateCallback = function (progress, status, pb) {
          if (progress == 100) {
            pb.stopMonitoring();
            window.location = window.location.href.split('op=')[0] +'op=finished';
          }
        }

        // Failure: point out error message and provide link to the summary.
        var errorCallback = function (pb) {
          var div = document.createElement('p');
          div.className = 'error';
          $(div).html('An unrecoverable error has occured. You can find the error message below. It is advised to copy it to the clipboard for reference. Please continue to the <a href=\"cvs_to_versioncontrol_project_update.php?op=error\">update summary</a>');
          $(holder).prepend(div);
          $('#wait').hide();
        }

        var progress = new Drupal.progressBar('updateprogress', updateCallback, \"POST\", errorCallback);
        progress.setProgress(-1, 'Starting updates');
        $(holder).append(progress.element);
        progress.startMonitoring('cvs_to_versioncontrol_project_update.php?op=do_update', 0);
      });
    });
  }
  ";
}

/**
 * Perform updates for one second or until finished.
 *
 * @return
 *   An array indicating the status after doing updates. The first element is
 *   the overall percentage finished. The second element is a status message.
 */
function update_do_updates() {
  while (isset($_SESSION['update_remaining']) && ($update = reset($_SESSION['update_remaining']))) {
    $update_finished = update_data($update['module'], $update['version']);
    if ($update_finished == 1) {
      // Dequeue the completed update.
      unset($_SESSION['update_remaining'][key($_SESSION['update_remaining'])]);
      $update_finished = 0; // Make sure this step isn't counted double
    }
    if (timer_read('page') > 1000) {
      break;
    }
  }

  if ($_SESSION['update_total']) {
    $percentage = floor(($_SESSION['update_total'] - count($_SESSION['update_remaining']) + $update_finished) / $_SESSION['update_total'] * 100);
  }
  else {
    $percentage = 100;
  }

  // When no updates remain, clear the caches in case the data has been updated.
  if (!isset($update['module'])) {
    cache_clear_all('*', 'cache', TRUE);
    cache_clear_all('*', 'cache_page', TRUE);
    cache_clear_all('*', 'cache_menu', TRUE);
    cache_clear_all('*', 'cache_filter', TRUE);
    drupal_clear_css_cache();
  }

  return array($percentage, isset($update['module']) ? 'Updating '. $update['module'] .' module' : 'Updating complete');
}

/**
 * Perform updates for the JS version and return progress.
 */
function update_do_update_page() {
  global $conf;

  // HTTP Post required
  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    drupal_set_message('HTTP Post is required.', 'error');
    drupal_set_title('Error');
    return '';
  }

  // Error handling: if PHP dies, the output will fail to parse as JSON, and
  // the Javascript will tell the user to continue to the op=error page.
  list($percentage, $message) = update_do_updates();
  print drupal_to_js(array('status' => TRUE, 'percentage' => $percentage, 'message' => $message));
}

/**
 * Perform updates for the non-JS version and return the status page.
 */
function update_progress_page_nojs() {
  drupal_set_title('Updating');

  $new_op = 'do_update';
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Error handling: if PHP dies, it will output whatever is in the output
    // buffer, followed by the error message.
    ob_start();
    $fallback = '<p class="error">An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference. Please continue to the <a href="cvs_to_versioncontrol_project_update.php?op=error">update summary</a>.</p>';
    print theme('maintenance_page', $fallback, FALSE, TRUE);

    list($percentage, $message) = update_do_updates();
    if ($percentage == 100) {
      $new_op = 'finished';
    }

    // Updates successful; remove fallback
    ob_end_clean();
  }
  else {
    // Abort the update if the necessary modules aren't installed.
    if (!module_exists('versioncontrol') || !module_exists('versioncontrol_project') || !module_exists('cvs')) {
      print update_finished_page(FALSE);
      return NULL;
    }

    // This is the first page so return some output immediately.
    $percentage = 0;
    $message = 'Starting updates';
  }

  drupal_set_html_head('<meta http-equiv="Refresh" content="0; URL=cvs_to_versioncontrol_project_update.php?op='. $new_op .'">');
  $output = theme('progress_bar', $percentage, $message);
  $output .= '<p>Updating your site will take a few seconds.</p>';

  // Note: do not output drupal_set_message()s until the summary page.
  print theme('maintenance_page', $output, FALSE);
  return NULL;
}

function update_finished_page($success) {
  drupal_set_title('CVS module to Version Control/Project Node integration module update.');
  // NOTE: we can't use l() here because the URL would point to 'update.php?q=admin'.
  $links[] = '<a href="'. base_path() .'">Main page</a>';
  $links[] = '<a href="'. base_path() .'?q=admin">Administration pages</a>';

  // Report end result
  if ($success) {
    $output = '<p>Updates were attempted. If you see no failures below, you should remove cvs_to_versioncontrol_project_update.php from your Drupal root directory. Otherwise, you may need to update your database manually. All errors have been <a href="index.php?q=admin/reports/dblog">logged</a>.</p>';
  }
  else {
    $output = '<p class="error">The update process was aborted prematurely. All other errors have been <a href="index.php?q=admin/reports/dblog">logged</a>. You may need to check the <code>watchdog</code> database table manually.</p>';
    $output .= '<p class="error">This has most likely occurred because the Version Control/Project Node integration module or the CVS module is not <a href=\"index.php?q=admin/build/modules\">properly installed</a>.</p>';
  }

  $output .= theme('item_list', $links);

  if ($success) {
    $output .= "<h4>Some things to take care of now:</h4>\n";
    $output .= "<ul>\n";
    $output .= "<li>Visit the <a href=\"index.php?q=admin/project/versioncontrol-settings/project\">Version control settings page for project integration</a>, and make any necessary adjustments.</li>\n";
    $output .= "<li>If you're all done with the old CVS module, <a href=\"index.php?q=admin/build/modules\">disable/uninstall it</a>.</li>\n";
    $output .= "</ul>\n";
  }

  // Output a list of queries executed
  if (!empty($_SESSION['update_results'])) {
    $output .= '<div id="update-results">';
    $output .= '<h2>The following queries were executed</h2>';
    foreach ($_SESSION['update_results'] as $module => $updates) {
      $output .= '<h3>'. $module .' module</h3>';
      foreach ($updates as $number => $queries) {
        $output .= '<h4>Update #'. $number .'</h4>';
        $output .= '<ul>';
        foreach ($queries as $query) {
          if ($query['success']) {
            $output .= '<li class="success">'. $query['query'] .'</li>';
          }
          else {
            $output .= '<li class="failure"><strong>Failed:</strong> '. $query['query'] .'</li>';
          }
        }
        if (!count($queries)) {
          $output .= '<li class="none">No queries</li>';
        }
        $output .= '</ul>';
      }
    }
    $output .= '</div>';
    unset($_SESSION['update_results']);
  }

  return $output;
}

function update_info_page() {
  drupal_set_title('CVS module to Version Control/Project Node integration module update.');
  $output = "<ol>\n";
  $output .= "<li>Use this script to <strong>upgrade an existing CVS module installation to the Version Control/Project Node integration module</strong>. You don't need this script when installing Version Control/Project Node integration from scratch.</li>";
  $output .= "<li>Before doing anything, backup your database. This process will change your database and its values.</li>\n";
  $output .= "<li>Make sure the Version Control/Project Node integration module and the old CVS module are <a href=\"index.php?q=admin/build/modules\">properly installed</a>.</li>\n";
  $output .= "<li>Make sure this file is placed in the root of your Drupal installation (the same directory that index.php is in) and <a href=\"cvs_to_versioncontrol_project_update.php?op=selection\">run the database upgrade script</a>. <strong>Don't upgrade your database twice as it will cause problems!</strong></li>\n";
  $output .= "</ol>";
  $output .= "<h2>Caveats</h2>\n";
  $output .= "<ul>\n";
  $output .= "<li>If a repository entry in the old CVS module lists more than one module in the 'Modules' field (at admin/project/cvs-repositories/edit/[repo_id]), the script will not correctly generate the new project paths to the repository directories (listed on project nodes, edit page, 'Version control integration' fieldset, 'Project directory').</li>\n";
  $output .= "</ul>\n";
  return $output;
}

function update_access_denied_page() {
  drupal_set_title('Access denied');
  return '<p>Access denied. You are not authorized to access this page. Please log in as the admin user (the first user you created). If you cannot log in, you will have to edit <code>cvs_to_versioncontrol_project_update.php</code> to bypass this access check. To do this:</p>
<ol>
 <li>With a text editor find the cvs_to_versioncontrol_project_update.php file on your system. It should be in the main Drupal directory that you installed all the files into.</li>
 <li>There is a line near top of cvs_to_versioncontrol_project_update.php that says <code>$access_check = TRUE;</code>. Change it to <code>$access_check = FALSE;</code>.</li>
 <li>As soon as the update is done, you should remove cvs_to_versioncontrol_project_update.php from your main installation directory.</li>
</ol>';
}

include_once './includes/bootstrap.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_maintenance_theme();

// Access check:
if (($access_check == FALSE) || ($user->uid == 1)) {

  $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
  switch ($op) {
    case 'Update':
      $output = update_update_page();
      break;

    case 'finished':
      $output = update_finished_page(TRUE);
      break;

    case 'error':
      $output = update_finished_page(FALSE);
      break;

    case 'do_update':
      $output = update_do_update_page();
      break;

    case 'do_update_nojs':
      $output = update_progress_page_nojs();
      break;

    case 'selection':
      $output = update_selection_page();
      break;

    default:
      $output = update_info_page();
      break;
  }
}
else {
  $output = update_access_denied_page();
}

if (isset($output)) {
  print theme('maintenance_page', $output);
}

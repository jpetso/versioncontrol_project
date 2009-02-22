<?php

// $Id$

/**
 * @file
 * Required and optional configuration variables for the packge release script.
 */

// ------------------------------------------------------------
// Required customization
// ------------------------------------------------------------

// The root of your Drupal installation, so we can properly bootstrap
// Drupal. This should be the full path to the directory that holds
// your index.php file, the "includes" subdirectory, etc.
// $drupal_root = '/var/www/my-drupal-installation';
$drupal_root = '';

// The name of your site. Required so that when we bootstrap Drupal in
// this script, we find the right settings.php file in your sites folder.
// $site_name = 'drupal.org';
$site_name = '';

// Root of the temporary directory where you want packages to be
// made. Subdirectories will be created depending on the task.
// $tmp_root = '/tmp';
$tmp_root = '';

// Location of the LICENSE.txt file you want included in all packages.
// $license = '/usr/share/common-licenses/GPL-2';
$license = '';

// Location of the INSTALL.txt file you want included in all
// translation packages.
$trans_install = '';


// ------------------------------------------------------------
// Optional customization
// ------------------------------------------------------------

// ----------------
// File destination
// ----------------
// This assumes you want to install the packaged releases in the
// "files/projects" directory of your root Drupal installation. If
// that's not the case, you should customize these.
$dest_root = $drupal_root;
$dest_rel = "sites/$site_name/files/projects";

// --------------
// External tools
// --------------
// If you want this program to always use absolute paths for all the
// tools it invokes, provide a full path for each one. Otherwise,
// the script will find these tools in your PATH.
$cvs = '/usr/bin/cvs';
$tar = '/bin/tar';
$gzip = '/bin/gzip';
$ln = '/bin/ln';
$rm = '/bin/rm';
$mkdir = '/bin/mkdir';
$msgcat = 'msgcat';
$msgattrib = 'msgattrib';
$msgfmt = 'msgfmt';

// The taxonomy id (tid) of the "Security update" term on drupal.org
define('SECURITY_UPDATE_TID', 100);

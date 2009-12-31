#!/usr/bin/env php
<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
Horde_Cli::init();

// Include needed libraries.
new Horde_Application(array('authentication' => 'none'));

// Authenticate as administrator.
if (!count($conf['auth']['admins'])) {
    exit("You must have at least one administrator configured to run the alarms.php script.\n");
}
Horde_Auth::setAuth($conf['auth']['admins'][0], array());

// Run
$horde_alarm = Horde_Alarm::factory();
$horde_alarm->notify(null, true, false, array('notify'));

<?php

###############################################################################
# english.php
#
# This is the English language file for the Geeklog indexnow plugin
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
###############################################################################


// Localization of the Admin Configuration UI
$LANG_configsections['indexnow'] = array(
    'label' => 'IndexNow',
    'title' => 'IndexNow Configuration'
);

$LANG_confignames['indexnow'] = array(
    'indexnow_key' => 'IndexNow key',
);

$LANG_configsubgroups['indexnow'] = array(
    'sg_0' => 'Main Settings',
);

$LANG_fs['indexnow'] = array(
    'fs_01' => 'IndexNow plugin'
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['indexnow'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE)
);
?>

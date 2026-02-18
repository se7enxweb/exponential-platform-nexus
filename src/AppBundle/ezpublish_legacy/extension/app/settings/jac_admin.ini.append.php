<?php /* #?ini charset="utf8"?

[AdminSettings]
PhpCli=/usr/bin/php

[AdminSubversionSettings]

#####
#
# EDITOR=joe sudo -E visudo
#
# #Defaults    requiretty
#
# lighttpd     ALL=(jac) NOPASSWD: /usr/bin/svn update *
# lighttpd     ALL=(jac) NOPASSWD: /usr/bin/svn status *
#
# http://www.sudo.ws/sudo/man/sudoers.html
#
#####

# if set sudo -H -u
ServerUser=

;LogDir=var_log/log

SvnReposArray[]
SvnReposArray[jac]=https://team.in-mv.com/svn/jac
SvnReposArray[ez]=https://team.in-mv.com/svn/ez
SvnReposArray[k_jacexample]=https://team.in-mv.com/svn/k_jacexample

SvnUserArray[]
SvnUserArray[jac]=svnwebserver
SvnUserArray[ez]=svnwebserver
SvnUserArray[k_jacexample]=svnwebserver

SvnPasswdArray[]
SvnPasswdArray[jac]=svnwebserver2007
SvnPasswdArray[ez]=svnwebserver2007
SvnPasswdArray[k_jacexample]=svnwebserver2007

ExtensionsExcludeList[]
ExtensionsExcludeList[]=ezxajax_roleedit
ExtensionsExcludeList[]=ezxajax_classattributes
ExtensionsExcludeList[]=developer
ExtensionsExcludeList[]=superuser

# if enabled  ezroot is used as svn base dir
SingleAppMode=enabled

[AdminLogviewSettings]

ServerLogFileArray[]
ServerLogFileArray[]=error_log
ServerLogFileArray[]=../ezpublish_legacy/error_log


#ServerLogFileArray[]=/var/log/nginx/{$project}.error.log
#ServerLogFileArray[]=/var/log/nginx/error.log
#ServerLogFileArray[]=/var/log/maillog
#ServerLogFileArray[]=/var/log/php-fpm/error.log
#ServerLogFileArray[]=/var/log/php-fpm/www-slow.log

;VarLogDir=var_log

[AdminMaintenanceSettings]
;VarCacheDir=var_cache

*/?>

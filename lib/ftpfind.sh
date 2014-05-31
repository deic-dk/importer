#!/usr/local/bin/bash
#
# Copyright, 2013, Frederik Orellana
#
# Usage: davfind.sh http://<hostname>:<port>/<upload path>\n".
# Findsa all files recursively to the WebDAV folder passed in the url.
#
# Dependencies: curl, xmllint
#

while getopts "u:p:" flag; do
case "$flag" in
    u) user=$OPTARG;;
    p) pass=$OPTARG;;
esac
done

url=${@:$OPTIND:1}
#ARG2=${@:$OPTIND+1:1}

if [[ ! $url =~ .*/$ ]]; then
	url=$url/
fi

user_str=""
if [ "$user" != "" ]; then
	user_str="-u $user"
fi
pass_str=""
if [ "$user" != "" ]; then
	pass_str=" -p $pass"
fi

if [ "$out_dir" == "" ]; then
	out_dir=`echo $url | sed -r 's|.*/([^/]+)/*$|\1|'`
fi

function ftppull(){

	local base=`echo $1 | sed -r 's|^(ftp*://*[^/]*)(/.*)$|\1|'`
	local path=`echo $1 | sed -r 's|^(ftp*://*[^/]*)(/.*)$|\2|'`

	ncftpls -m ${user_str}${pass_str} "$1" | sed -r 's|.*type=dir;.*; (.*)$|\1/|' | sed 's|//|/|g' | sed -r 's|.*type=file;.*; (.*)$|\1|' | grep -v '^type=' | grep -v ';type=' |

	while read name; do
		if [[ $name =~ .*/$ ]]; then
			ftppull "${base}${path}${name}"
		else
			echo ${base}${path}${name}
		fi
	done

}

ftppull "$url"




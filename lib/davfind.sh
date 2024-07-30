#!/usr/local/bin/bash
#
# Copyright, 2013, Frederik Orellana
#
# Usage: davfind.sh http://<hostname>:<port>/<upload path>\n".
# Finds all files recursively in the WebDAV folder passed in the url.
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

user_pass=""
if [ "$user" != "" ]; then
	user_pass="--user $user:$pass"
fi

if [ "$out_dir" == "" ]; then
	out_dir=`echo $url | sed -r 's|.*/([^/]+)/*$|\1|'`
fi

function davpull(){

	local base=`echo $1 | sed -r 's|^(https*://*[^/]*)(/.*)$|\1|'`
	local path=`echo $1 | sed -r 's|^(https*://*[^/]*)(/.*)$|\2|'`

	curl -k -L --request PROPFIND --header "Depth: 1" "$user_pass" "$1" 2>/dev/null | xmllint --format - 2>/dev/null | grep href | sed -r 's|^ *||' | sed -r 's|<d:href>(.*)</d:href>|\1|' | sed -r "s|^/*$path/*||" | grep -v '^\./$' | grep -v '^$' |

	while read name; do
		if [[ $name =~ .*/$ ]]; then
			davpull "${base}${path}${name}"
		else
			echo "${base}${path}${name}"
		fi
	done

}

davpull "$url"




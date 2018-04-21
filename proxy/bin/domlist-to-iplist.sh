#!/bin/bash
#
# Converting domains list from text file into IP addresses lists
#

INFILE=$1

if [ -z "${INFILE}" ]; then
	echo "Domains list file should be specified as an argument."
	exit 1
fi

if [ ! -r "${INFILE}" ]; then
	echo "File ${INFILE} is not readable"
	exit 2
fi

while IFS='' read -r domname || [[ -n "$domname" ]]; do
    host $domname | grep "has address" | awk '{print $4}'
#    echo $domname
done < "${INFILE}"

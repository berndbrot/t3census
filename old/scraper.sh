#!/bin/bash
if [ "$1"  != "" ]; then
	output=$1
fi
while read ipAddress; do
	numResults=-1;
	first=1;
	if [ "" == "$output" ]; then
		echo from $ipAddress:
	else
		echo from $ipAddress: >> $output
	fi
	while [ "$first" -le "$numResults" ] || [ "$numResults" -eq -1 ]; do
		res=$(curl "http://www.bing.com/search?q=ip%3a$ipAddress&first=$first" 2>/dev/null)
		echo from page $first:
		if [ $numResults -eq -1 ]; then
			numResults=$(echo $res | grep '<span[^>]*class="sb_count"[^<]*</span' -o | grep "[0-9]* " -o | grep -o '[0-9]*')
			if [ "$numResults" == "" ]; then
				break;
			fi
		fi
		if [ "" == "$output" ]; then
			echo $res | grep -o '<cite>[^<]*</cite>' | grep -o '<cite>[^<]*' | grep -o '[^>]*$'
		else
			echo $res | grep -o '<cite>[^<]*</cite>' | grep -o '<cite>[^<]*' | grep -o '[^>]*$' >> $output
		fi
		let first=$first+10
	done
done

#!/bin/bash
PID_PATH='{{PID_PATH}}'

case "$1" in
start)
	# check if PID file exists
	if [ -f $PID_PATH ]; then
		pid="`cat $PID_PATH`"
		if ps $pid > /dev/null
		then
			echo -e "\e[31mLeptir is already flying on this box.\e[0m"
			exit 1
		else
			echo -e "\e[33m.pid file is there, but process is not running. Cleaning .pid file and starting the process.\e[0m"
			rm -f "$PID_PATH"
		fi
	fi
	echo -e "\e[32mStarting a little butterfly. Fly buddy, fly!\e[0m"
    {{PHP_PATH}}php {{ROOT_PATH}}/public/index.php leptir start  --config={{CONFIG_PATH}} --daemon --pid $PID_PATH
;;
stop)
    echo -ne "Stopping a little butterfly. You'll have to wait for all the tasks to finish though.\n"
	{{PHP_PATH}}php {{ROOT_PATH}}/public/index.php leptir stop --pid $PID_PATH
	while [ -f $PID_PATH ];
	do
		sleep 1
		echo -ne "."
	done
	echo
;;
*)
    echo "Usage: $0 (start|stop)"
    exit 1
esac

exit 0

#!/bin/bash
find /home/ae4a9cb4/nav.darlighting.co.uk/nav_proc_logs -type f -name "*.log" -mtime +30 -exec rm -v "{}" \;
find /home/ae4a9cb4/nav.darlighting.co.uk/nav_proc_logs -type d -empty -exec rmdir -v "{}" \;
find /home/ae4a9cb4/live.darlighting.co.uk/db_proc_logs -type f -name "*.log" -mtime +30 -exec rm -v "{}" \;
find /home/ae4a9cb4/live.darlighting.co.uk/db_proc_logs -type d -empty -exec rmdir -v "{}" \;
find /home/ae4a9cb4/live.darlighting.co.uk/nav_proc_logs -type f -name "*.log" -mtime +30 -exec rm -v "{}" \;
find /home/ae4a9cb4/live.darlighting.co.uk/nav_proc_logs -type d -empty -exec rmdir -v "{}" \;

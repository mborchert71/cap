sample - cap

notice: 
	no interface , until fully statisfied with code and fully documented

	why the folders.
	as I always preferred the server to be responsible for authentication (i even like the popup)
	at the same time i always disliked url_rewriting and manipulation (mod_rewrite).
	the url should be read as clear as possible, thats the reason having alot of folders
	with nothing in it but an index.php.
	
	that is one  the main reasons for this little project:
	having an unified request-handling of sql-queries
	 
Todo
	doku
	mit und nach doku optimize
	write comments
	web-usage :
		# not designed for moviedatabase or large scale data-sets 
		# global-player-sites offer huge data-amount >>>	personalized small snapshot
	 	# there is no guarantie that the g-p-s keep and/or offer! the data. 
	 		(not even on paysites >>> go to Ford and tell them you want to buy an 1934 Oldtimer,
	 			well it might/will become possible ; as soon as all plans are digitized and the factories and their robots become more flexible
	 			but thats another story...)
		# cloud is great,but in a personal point of view its better having a real world disc. 
	option : cache last query on insert , refuse insert on a too short delay having duplicate data
	todos in file
	tests

Version 0.0.0


Changes
2011-06-25	ported from mysql to sqlite , if table not exist automatic creation
2011-06-16	datamodel + queries moved to config.inc . sqlite connection
2011-06-15	introduced "cap" the php copy + api + paste kit.
2011-06-14	ripped from timeline-projekt
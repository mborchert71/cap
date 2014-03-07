** CAP **

Copy And Paste database-api

+ global-player-sites have/offer huge data-amount >>> customers have/get personalized small snapshot
	
+ there is no guarantie that the g-p-s keep and/or offer! the data in future.

+ cloud is great, but from  private point of view the personal data's original source should be at home! 

+ cap uses folders, sqlite and php-pdo to create a simple way of having easy access to a database via the internet.
	
+ access via GET and/or POST.

+ See the Sample-FolderContent to get a clue. 


todo:
	tests,db-index, handle joins and union 

Version 0.3.0

Changes
2011-07-04	darum alles von tabellen-fokus zum datenbank-fokus, 'get' + 'set' für sinnigere url: sample/get/?next_id == sample/?do=next_id == sample/?next_id
          	kaum eine zahl als version festgelegt schon ist das Ding unbrauchbar. echt[cursed]
2011-07-03	deleted class.php cause its not needed in an single-sample environment;finished query token (left out:union,join,on)
2011-06-25	ported from mysql to sqlite , if table not exist automatic creation
2011-06-16	datamodel + queries moved to config.inc . sqlite connection
2011-06-15	introduced "cap" the php copy + api + paste kit.
2011-06-14	ripped from timeline-projekt

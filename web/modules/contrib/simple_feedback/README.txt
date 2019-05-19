Installation
=========================
1. Enable the module
2. Configure the "Simple Feedback Block" in admin > structure > blocks


How to retrieve data
=========================
The current version only allows for a "yes" and "no" vote. Each vote is entered
into the database table "simple_feedback" with yes value of 1 and no value of -1

To retrieve and aggregate the data, you can do a simple database query like this
	SELECT nid, sum(value) FROM simple_feedback GROUP BY nid


Roadmap
=========================
Future releases of this module will include a configuration screen and a views
integration to build reports.

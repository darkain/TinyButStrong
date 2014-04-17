TinyButStrong
=============

This is a fork of the TinyButStrong library in order to fix
some issues with it, as well as optimize key areas of the library.

Date formatting is not tell defined in the original TBS library,
and my aim is to fix this.

Example:

20140416 - will read as 2014y, 04m, 16d @ 00:00:00
20142000 - will read as 1970y, 08m, 22d @ 03:00:00

This inconsistancy is because the first is read as a ISO date
format and the latter read as a Unix Timestamp. 

In this fork, the "guessing" to translate numbers to dates/times
has been removed. All numerical values for dates are now assumed
to be Unix Timestamps. ISO Date formats in their long-form are
also supported: ex 2014-04-16. The library sees the non-numerical
characters and processes it as an ISO Date format as expected.

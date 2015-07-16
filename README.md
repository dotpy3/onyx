Onyx
==========

What is Onyx ?
----------

Onyx is a ticketing system designed for the student clubs at the [University of Technology of Compiègne](http://www.utc.fr), in particular for the event clubs, to allow students to purchases tickets online using their CAS. It was originally conceived for the needs of the [Soirée des Finaux](http://assos.utc.fr/soireedesfinaux)'s (Finals Party) needs in 2015, but with in mind the idea of creating a general ticketing system that all clubs could use.

Why Onyx ?
----------

The conception of Onyx came from a study of the existing solutions :
* [ShotgunUTC](http://assos.utc.fr/shotgun/) is efficient for simple event planning, but has a very poor management and gets very impractical when it gets to sophisticated price planning: doesn't allow non-UTCeans to make purchases, poor tariff availability management...
* [The Polar's ticketing system](http://assos.utc.fr/polar/) is a huge step forward, but may not fulfill huge student event's needs - in particular when the communication around these events revolve a certain visual identity or experience, making customers go on the Polar's website to order can be an inconvenience.
* An elaborate ticketing system was conceived by Matthieu Guffroy and the PayUTC team for the [Imaginarium Festival](http://www.imaginariumfestival.com)'s needs (and later used by [Etuville](http://assos.utc.fr/etuville) and for the [Soirée des Finaux 2014](http://assos.utc.fr/soireedesfinaux)). Although it was powerful and very user-friendly, the code was very difficult to reuse, being a homemade architecture.
* And, of course, using an external ticketing system was out of the question, since [PayUTC](http://assos.utc.fr/payutc) allows us a very fair 0.7% margin on all transactions.

The idea was to conceive a system that would be efficient, that would allow easy visual modification and changes, and that would be easy to read and to re-use codewise.
That is why we decided to go for a Symfony2 backed-up system - where views are clearly separated from the controller (allowing easy visual modifications without in-depth back-end knowledge) and where the code is clearly organized.

How to install Onyx
----------

To install Onyx, you need :
* A PHP server with a MySQL database (the SIMDE servers will fit just fine), and Symfony2-ready
* A Ginger key with access to login, name, adulthood, BDE contribution, student card RFID chip information. Ginger is a service conceived by the [SIMDE](http://assos.utc.fr/simde) that can give you access to student information. If you don't have a key, you should ask them (and specify what informations you need)
* A PayUTC foundation key (usually, corresponding to your student club) and WEBSALE app key. You can ask directly Nemopay (or through the BDE) for those.

How to install Onyx
----------

Foremost, if you don't have knowledge in Symfony2, I recommend checking out the official Symfony2 documentation or the OpenClassrooms guide to understand how things run.

1. First, you need to get all the vendors, by using Composer to install all the components, from the composer.json.
Get in the terminal (assuming you use an Unix machine), go to the root folder and type "curl -sS https://getcomposer.org/installer | php". Then, type run Composer by typing "php composer.phar install".
2. Change the database parameters by changing the parameters in app/config/parameters.yml.dist. Note: if you want to use a local and a distant configuration, you can create a parameters.yml file with the same syntax ; parameters.yml.dist is only used if there is no parameters.yml.
3. Add in src/SDF/BilletterieBundle/Controller/billetController.php and connexionController.php the Ginger key, and in the billetController.php the PayUTC access keys. The according variables are in the controller classes.
4. Add your database parameters in connexionController.php - in the controller, that's the .
5. For the database, two choices :
* Either your server allows you directly to use php software, then go to your root folder, and run this command: "php app/console doctrine:schema:update --force"
* Either your server doesn't allow this (like the SIMDE servers), and in that case you should use the init.sql file at the root of the folder. Copy these and execute them in your phpMyAdmin interface.

Voilà ! The system is accessible at web/billetterie. If an error occurs, you can check out the details by adding "app_dev.php/" just after "web/" (for example, web/app_dev.php/billetterie).

If you want the system to be available for external users (creating a login/password identification system), you need to make the $exterieurAccess variable true in DefaultController.php. Else, put it at false. (But you should anyway put the according tariff to "not accessible for external users", security-wise)

How to use Onyx
----------

1. Connect for the first time.
2. 

To-do list
----------

Right now, the code proved itself to be efficient, and worked successfully for the Soirée des Finaux 2015 (that happened in June). However, there are still many things that can be done to make this system better.
* Refactoring all the code - partly done, but many parts can still be improved.
* Documenting the code.
* Conception of a user-friendly administration, that would allow both easy creation of tickets and a real-time check system. For example, during the Soirée des Finaux, when there was a problem at the entrance (someone that forgot their ticket, a ticket that is validated twice, etc) it was a pain to check directly into the SQL database.
* Protect access to JSON calls by using keys - this is already implemented for the ticket validation part, but can be extended to other parts.

Thanks
----------

Many thanks in particular to Guillaume Vassal, the Nemopay team and the SIMDE team.
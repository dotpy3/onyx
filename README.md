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

Foremost, if you don't have knowledge in Symfony2, I recommend checking out the official Symfony2 documentation or the OpenClassrooms guide to understand how things run.

1. First, you need to get all the vendors, by using Composer to install all the components, from the composer.json.
Get in the terminal (assuming you use an Unix machine), go to the root folder and type "curl -sS https://getcomposer.org/installer | php". Then, type run Composer by typing "php composer.phar install".
2. Change the database parameters by changing the parameters in app/config/parameters.yml.dist. Note: if you want to use a local and a distant configuration, you can create a parameters.yml file with the same syntax ; parameters.yml.dist is only used if there is no parameters.yml.
3. Add in src/SDF/BilletterieBundle/Controller/billetController.php and connexionController.php the Ginger key, and in the billetController.php the PayUTC access keys. The according variables are in the controller classes.
4. Add your database parameters in connexionController.php - in the controller, that's the .
5. For the database, two choices : Either your server allows you directly to use php software, then go to your root folder, and run this command: "php app/console doctrine:schema:update --force". Either your server doesn't allow this (like the SIMDE servers), and in that case you should use the init.sql file at the root of the folder. Copy these and execute them in your phpMyAdmin interface.

Voilà ! The system is accessible at web/billetterie. If an error occurs, you can check out the details by adding "app_dev.php/" just after "web/" (for example, web/app_dev.php/billetterie).

If you want the system to be available for external users (creating a login/password identification system), you need to make the $exterieurAccess variable true in DefaultController.php. Else, put it at false. (But you should anyway put the according tariff to "not accessible for external users", security-wise)

How to use Onyx
----------

How to add admins: Once a user has connected, go to the phpMyAdmin administration, and set his "admin" attribute to 1.

How to add tariffs and set constraints:
To understand better how constraints are set for a tariff you can check out this part of this UML: (don't pay attention to the class attributes, they are deprecated)
![(Whoopsie, not available!)](http://www.pixenli.com/images/1437/1437080138086693600.png)

A tariff is defined by :
* A constraint. A constraint object defines the beginning and end of the sales, if the user must be a BDE contributor, if he must not be a BDE contributor (not in the picture!!! but it's the doitNePasEtreCotisant attribute) and if external users can access this tariff. I've thought about merging the tariff with its constraint object, but I figured out there might be cases where you'd want to use the same constraints for various tariffs.
* An event. An event is defined by a name, and by a maximum quantity of tickets that can be purchased within this event. Example: I create an event called "UTCéenne 2015", that has a quantiteMax of 3500 ; and I associate to this event two tariffs, "Cotisant BDE" of quantity 2500 and "Non cotisant" of quantity 2000. If I sell 1500 "Cotisant BDE" and 2000 "Non cotisant", the sale of "Cotisant BDE" will stop too, because the event capacity has been capped.
* A common jar, called "Pot commun". When a user purchases a ticket within this jar, he can't buy any other ticket in this jar. e.g. if you create three tariffs "Cotisant 1" "Cotisant 2" and "Cotisant 3" and put them in the same jar, if a user buys "Cotisant 1", he can't buy "Cotisant 2" or "Cotisant 3" anymore.

To create these and to create your tariff, you can access web/billetterie/admin, which gives you access to the links to the generation forms.

Once the tariff is accessible, people can buy tickets corresponding to this tariff until it runs out. Each tickets is defined by :
* Its user.
* Its tariff.
* Its shuttle (can be null). Shuttles are defined by their trajectory.

To-do list
----------

Right now, the code proved itself to be efficient, and worked successfully for the Soirée des Finaux 2015 (that happened in June). However, there are still many things that can be done to make this system better.
* Refactoring all the code - partly done, but many parts can still be improved.
* Documenting the code.
* Conception of a user-friendly administration, that would allow both easy creation of tickets and a real-time check system. For example, during the Soirée des Finaux, when there was a problem at the entrance (someone that forgot their ticket, a ticket that is validated twice, etc) it was a pain to check directly into the SQL database.
* Protect access to JSON calls by using keys - this is already implemented for the ticket validation part, but can be extended to other parts.
* BUG TO BE FIXED: when using checkValidNumBilletAction in billetController.php to check if a ticket is valid by its number, the function automatically uses Ginger to check if the user is legal, or else assumes he is legal. This function must be corrected to take into account the birthday date of the user if he has an external account.

Thanks
----------

Many thanks in particular to Guillaume Vassal, the Nemopay team and the SIMDE team.
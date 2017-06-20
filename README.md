## dizzyprice NetHack 3.6.0 price identification calculator

A little while back, [NetHack](http://nethack.org/) finally released version
3.6.0, after more than 10 years with no updates. As a part of this release,
the C source calculating the prices of items in shops changed slightly,
resulting in all existing Price ID calculators online to cease to function.

In an effort to fill this need, and as an excuse to learn Python, I wrote
this little script, as well as a PHP front-end so that it can be accessed
online rather than locally. This is dizzyprice.

I host this at [my website](http://nethack.raech.net/dizzyprice.php) (this
link may be down for a while), so you can see it in action.

### Example usage

When calling dizzyprice from the command line, it should be fairly flexible.
For details on all available options, run it with the argument `-h` to see
the help screen.

If you were buying a ring, say, and the shopkeeper was charging you 400 gold
and you have a charisma of 15, You might run this:

    $ ./dizzyprice.py -b -c 15 ring 400

    Base	Price1	Price2	Item
    300     300     400     ring of conflict
    300     300     400     ring of teleport control
    300     300     400     ring of polymorph
    300     300     400     ring of polymorph control

The default charisma is 10/11 (they are the same in NetHack calculations),
so you can leave off that argument if you have that happy medium.

NetHack has two prices that can be calculated, which varies based on the
object number that the executable uses for a generated item, a 1 in 4 chance
existing that Price2 is the offered price. Let's say the shopkeeper was
offering you 75 gold for a potion; what could it be?

    $ ./dizzyprice.py -sc 8 potion 75

    Base    Price1  Price2  Item
    150     75      56      potion of blindness
    200     100     75      potion of speed
    200     100     75      potion of levitation
    150     75      56      potion of invisibility
    200     100     75      potion of enlightenment
    150     75      56      potion of monster detection
    150     75      56      potion of object detection
    150     75      56      potion of gain energy
    200     100     75      potion of full healing
    200     100     75      potion of polymorph

For armor and weapons, you can type an arbitrary string as part of the
query, operating as a filter. For example:


    $ ./dizzyprice.py -s weapon da 17

    Base    Price1  Price2  Item
    34      17      13      +3 dagger
    44      22      17      +4 dagger
    34      17      13      +3 elven dagger
    44      22      17      +4 elven dagger
    34      17      13      +3 orcish dagger
    44      22      17      +4 orcish dagger

In fact, for weapons and armor, you can omit the "weapon" or "armor" bit
entirely, and just go for the grep. A tourist shelling out 66 gold for a
pair of boots might do:

    $ ./dizzyprice.py -b -t boots 66

    Base    Price1  Price2  Item
    28      50      66      +2 low boots
    28      50      66      +2 elven boots
    28      50      66      +2 kicking boots

### dizzyprice PHP front-end

Check the README.md file in the `web` folder for information on setting up
the front-end.

### License

This program is licensed under the Affero GPLv3+, so if you put it online,
be sure to link to the source. :) This page will do.

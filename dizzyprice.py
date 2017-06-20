#!/usr/bin/env python3
#
# dizzyprice - NetHack Price ID tool
# "The shopkeeper's gaze confuses you!"
#
# For demonstration, run something like...
#   ./dizzyprice.py -bc 8 buckled 15

__author__ = "Aubrey Raech"
__copyright__ = "Copyright 2016, Aubrey Raech"
__credits__ = ["NetHack DevTeam", "rsarson", "sea"]
__license__ = "AGPLv3"
__version__ = "0.1"
__maintainer__ = "Aubrey Raech"
__email__ = "aubrey@raech.net"
__status__ = "Development"

import sys
import argparse
import objects

def get_buy_mods(charisma, istourist, duncecap):
    multiplier, divisor = 1, 1

    # consider tourism/dunce
    if istourist or duncecap:
        multiplier *= 4
        divisor *= 3

    # consider charistma
    if charisma > 18:
        divisor *= 2
    elif charisma == 18:
        multiplier *= 2
        divisor *= 3
    elif charisma >= 16:
        multiplier *= 3
        divisor *= 4
    elif charisma <= 5:
        multiplier *= 2
    elif charisma <= 7:
        multiplier *= 3
        divisor *= 2
    elif charisma <= 10:
        multiplier *= 4
        divisor *= 3

    pricea = [multiplier, divisor]
    priceb = [multiplier * 4, divisor * 3] # 1/4 of the time, arbitrary surcharge
    return [pricea, priceb]

def get_sell_mods(istourist, duncecap):
    multiplier, divisor = 1, 1

    # consider tourism/dunce
    if istourist or duncecap:
        divisor *= 3
    else:
        divisor *= 2

    # FIXME this discovery thing is conditional on a lot of things
    # figure out whether it's relevant and for which types of items
    offera = [multiplier, divisor]
    offerb = [multiplier * 3, divisor * 4] # if not discovered, possibly
    return [offera, offerb]

def get_new_prices(basecost, modifiers):
    prices = []
    for modifier in modifiers:
        multiplier = modifier[0]
        divisor = modifier[1]
        tmp = basecost * multiplier
        if divisor > 1:
            tmp *= 10
            tmp //= divisor
            tmp += 5
            tmp //= 10
        if tmp < 1:
            tmp = 1
        prices.append(tmp)
    return prices

def calculate_costs(items, mods):
    for item in items:
        prices = get_new_prices(item[3], mods)
        for price in prices:
            item.append(price)

def get_price_matches(initial_items, mods, zorkmids, search_values):
    if len(search_values) > 0:
        final_items = prune_list(initial_items, search_values[:])
    else:
        final_items = initial_items
    calculate_costs(final_items, mods)
    matches = []
    for entry in final_items:
        if (entry[4] == zorkmids) or (entry[5] == zorkmids):
            matches.append(entry)
    return matches

# for the future; base cost matches
# FIXME should probably call get_expanded_magic_list? maybe?
def get_base_matches(items, mods, grepper):
    calculate_costs(items, mods)
    matches = []
    for item in items:
        if (item[-3] == grepper):
            matches.append(item)
    return matches

def prune_list(items, search_values):
    pruned = []
    for item in items:
        if (item[0].find(search_values[0]) >= 0) or (item[1].find(search_values[0]) >= 0):
            pruned.append(item)
    if (len(search_values) <= 1):
        return pruned
    return prune_list(pruned[:], search_values[1:])

def expand_enchantments(items):
    expanded_items = [] # with +0,+7 bonuses... only some armor goes that high FIXME?
    for item in items:
        for i in range(8):
            this = item[:]
            this[0] = '+{} {}'.format(str(i), this[0])
            this[-1] += i * 10
            expanded_items.append(this)
    return expanded_items

# Deep copy for object data to avoid modifying objects.*
def copy_data(itemtype):
    newdata = []
    for item in itemtype[:]:
        newdata.append(item[:])
    return newdata

# temporary for testing
def errprint(items):
    for item in items:
        print(item)
        
def niceprint(items):
    if len(items) == 0:
        print("No items found.")
    else:
        print("Base\tPrice1\tPrice2\tItem")
        for item in items:
            print("{}\t{}\t{}\t{}".format(item[3], item[4], item[5], item[0]))

def htmlprint(items):
    if len(items) == 0:
        print("<p>No items found.</p>");
    else:
        print("<table class=\"dizzyout\">")
        print("<tr><th>Item</th><th>Base</th><th>Price 1</th><th>Price 2</th></tr>")
        for item in items:
            print("<tr><td>{}</td><td>{}</td><td>{}</td><td>{}</td></tr>".format(
                item[0], item[3], item[4], item[5]))
        print("</table>")
            
#
# Argument parsing
#
            
def validate_charisma(parser, arg):
    arg = int(arg)
    if arg < 3 or arg > 20:
        parser.error("Charisma should be between 3 and 20.")
    return arg

def isint(value):
    try:
        int(value)
        return True
    except:
        return False

def parse_lookup_string(parser, arglist):
    if len(arglist) < 2:
        parser.error("Minimum of 2 arguments:  [ITEM  PRICE]")
        
    value = arglist[-1]
    if not isint(value):
        parser.error("Final argument must be an integer.")
        sys.exit(0)
    if int(value) < 1:
        parser.error("Price must be positive.")
        sys.exit(0)
    lookup = [item.lower() for item in arglist[:-1]]

    # FIXME checking isalpha fails for '-', etc
    for item in lookup:
        if not item.isalpha():
            parser.error("Numbers permitted only in final argument.")
            sys.exit(0)

    # Okay finally! Start comparing to ring[s]/wand[s]/etc
    # save a lil memory if we can
    if lookup[0] == 'amulet' or lookup[0] == 'amulets':
        return ["amulets", value]
    elif lookup[0] == 'spellbook' or lookup[0] == 'spellbooks':
        return ["spellbooks", value]
    elif (lookup[0] == 'wand') or (lookup[0] == 'wands'):
        return ["wands", value]
    elif lookup[0] == 'potion' or lookup[0] == 'potions':
        return ["potions", value]
    elif lookup[0] == 'scroll' or lookup[0] == 'scrolls':
        return ["scrolls", value]
    elif lookup[0] == 'ring' or lookup[0] == 'rings':
        return ["rings", value]
    elif lookup[0] == 'tool' or lookup[0] == 'tools': # FIXME does this need desc?
        return ["tools", value]
    elif lookup[0] == 'stone' or lookup[0] == 'stones':
        return ["stones", value]
    elif lookup[0] == 'weapon' or lookup[0] == 'weapons':
        return ["weapons", value, lookup[1:]]
    elif lookup[0] == 'armor' or lookup[0] == 'armors':
        return ["armor", value, lookup[1:]]
    else:
        return ["all", value, lookup]

# Returning copy preserves original data for future lookups
def get_table_from_string(string):
    if string == "amulets":
        return copy_data(objects.amulets)
    elif string == "spellbooks":
        return copy_data(objects.spellbooks)
    elif string == "wands":
        return copy_data(objects.wands)
    elif string == "potions":
        return copy_data(objects.potions)
    elif string == "scrolls":
        return copy_data(objects.scrolls)
    elif string == "rings":
        return copy_data(objects.rings)
    elif string == "tools":
        return copy_data(objects.tools)
    elif string == "stones":
        return copy_data(objects.stones)
    elif string == "weapons":
        table = copy_data(objects.weapons)
        return expand_enchantments(table)
    elif string == "armor":
        table = copy_data(objects.armor)
        return expand_enchantments(table)
    elif string == "all":
        # oh boy :(
        table = copy_data(objects.amulets) \
                + copy_data(objects.spellbooks) \
                + copy_data(objects.wands) \
                + copy_data(objects.potions) \
                + copy_data(objects.scrolls) \
                + copy_data(objects.rings) \
                + copy_data(objects.tools) \
                + copy_data(objects.stones) \
                + expand_enchantments(copy_data(objects.weapons)) \
                + expand_enchantments(copy_data(objects.armor))
        return table
    else:
        return []

#
# Actual start of program!
#

tourist = False
dunce = False
buying = True
charisma = 10
price = 1

parser = argparse.ArgumentParser(description="NetHack Price ID Calculator")
group = parser.add_mutually_exclusive_group()
parser.add_argument("--html", help="Change output to HTML table",
                    action="store_true")
group.add_argument("-b", "--buying", help="Set mode to Buying (default)",
                    action="store_true")
group.add_argument("-s", "--selling", help="Set mode to Selling",
                    action="store_true")
parser.add_argument("-v", "--version", help="Print version information.",
                    action="store_true")
parser.add_argument("-t", "--tourist", help="Tourist XPLVL<15, or wearing hawaiian shirt.",
                    action="store_true")
parser.add_argument("-d", "--dunce", help="You are wearing a dunce cap.",
                    action="store_true")

parser.add_argument("-c", "--charisma", help="Set charisma attribute, between 3 and 20. (default 10)",
                    type=lambda x: validate_charisma(parser, x))
parser.add_argument('lookup_string', help="[obj type] [amount]", nargs=argparse.REMAINDER)

args = parser.parse_args()

if args.version:
    print("\ndizzyprice - NetHack Price ID tool")
    print("version " + __version__)
    print(__copyright__)
    print("Licensed under the " + __license__)
    sys.exit(0)

if args.html:
    html_out = True
else:
    html_out = False
    
if args.dunce:
    dunce = True
if args.tourist:
    tourist = True
if args.charisma:
    charisma = int(args.charisma)

if args.buying:
    buying = True
elif args.selling:
    buying = False

if buying:
    mods = get_buy_mods(charisma, tourist, dunce)
else:
    mods = get_sell_mods(tourist, dunce)
    
#print("\nCurrent settings:")
#print("-----------------")
#print("Tourist:  {}".format("Yes" if tourist else "No"))
#print("Dunce:    {}".format("Yes" if dunce else "No"))
#print("Mode  :   {}".format("Buying" if buying else "Selling"))
#print("Charisma: {}".format(str(charisma)))
#print()

lookup_args = parse_lookup_string(parser, args.lookup_string)    
obj_table = get_table_from_string(lookup_args[0])
value = int(lookup_args[1])

if len(lookup_args) == 2: # FIXME FIXME FIXME FIXME  SHOULD NEVER HAPPEN
#    print("Table: {}".format(lookup_args[0]))
#    print("Value: {}".format(lookup_args[1]))
    search_terms = []
else:
#    print("Table: {}".format(lookup_args[0]))
#    print("Value: {}".format(lookup_args[1]))
#    print("Terms: {}".format(lookup_args[2]))
    search_terms = lookup_args[2]
print()

if html_out:
    htmlprint(get_price_matches(obj_table, mods, value, search_terms))
else:
    niceprint(get_price_matches(obj_table, mods, value, search_terms))

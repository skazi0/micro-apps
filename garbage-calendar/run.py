#!/usr/bin/env python3
import os
import re
import yaml
from datetime import datetime, timedelta
from dateutil.parser import isoparse
import caldav
import vobject
import requests
from collections import defaultdict
import logging

# for address_id, grab "lokalizacja" param after searching at https://ekosystem.wroc.pl/gospodarowanie-odpadami/harmonogram-wywozu-odpadow/
with open(os.path.join(os.environ['HOME'], '.garbage-calendar.yaml'), 'r') as f:
    CFG = yaml.safe_load(f)

url = 'https://ekosystem.wroc.pl/wp-admin/admin-ajax.php'

# create calendars for each type "Garbage Paper", "Garbage Mixed", ...
whats = {
    'Paper': 'papier',
    'Mixed': 'zmieszane',
    'Bio': 'BIO',
    'Plastic': 'tworzywa',
    'Glass': 'szkło',
}

logging.basicConfig()
logger = logging.getLogger()
#logger.setLevel(logging.DEBUG)

startdate = datetime.now()
enddate = datetime.now()+timedelta(days=12*31)

def create_event(date, title):
    cal = vobject.iCalendar()
    cal.add('vevent')
    cal.vevent.add('summary').value = title
    cal.vevent.add('dtstart').value = date
    cal.vevent.add('dtend').value = date + timedelta(days=1)
    logger.debug("Event: %s", cal.serialize())
    return cal.serialize()

# fetch schedule data
schedule = defaultdict(list)
r = requests.post(url, data={'action': 'waste_disposal_form_get_schedule', 'id_numeru': CFG['address_id'] })
msg = r.json()['wiadomosc']
logger.debug("Schedule msg: %s", msg)
m = re.search('href="[^"]+\?([^"]+)"', msg)
params = dict(p.split('=') for p in m.group(1).split('&'))
logger.debug("Schedule data: %s", params)
for i in range(1, int(params['params'])+1):
    what = params['co_%d' % i]
    when = params['kiedy_%d' % i]
    schedule[what].append(when)
logger.debug("Parsed schedule: %s", schedule)

# store the events in calendars
client = caldav.DAVClient(CFG['url'], username=CFG['user'], password=CFG['password'])
principal = client.principal()
calendars = principal.calendars()
for calendar in calendars:
    if 'Garbage' not in calendar.name:
        continue

    what = whats[calendar.name.split(' ')[-1]]

    logger.debug("Using calendar '%s' (%s) for '%s'", calendar.name, calendar.url, what)

    logger.debug("Looking for events")
    results = calendar.date_search(startdate, enddate)

    # remove old schedule (keep past events)
    for event in results:
         event.delete()

    # add new events
    for event in schedule[what]:
        calendar.add_event(create_event(isoparse(event).date(), 'Wywóz %s' % what))

# data.dzsonline.nl

## Demo/examples:

https://data.dzsonline.nl/demo.html

## Eindpoints:

**Upcomming games**:
https://data.dzsonline.nl/programma.json

**Played games**:
https://data.dzsonline.nl/uitslagen.json

**All games**:
https://data.dzsonline.nl/wedstrijden.json

**Teams** (per klasse):
https://data.dzsonline.nl/teams.json

**Teams, sorted by position and with detailed information about points and played games** (per klasse):
https://data.dzsonline.nl/standen.json

**All together in a single request**:
https://data.dzsonline.nl/data.json

## Import

https://data.dzsonline.nl/import
## Webcomponents

**Include JavaScript:**
```html
<script src='//data.dzsonline.nl/dzs-webcomponents.js' type='text/javascript'></script>
```

### Standings/teams:

**Summary on the homepage**

```html
<dzs-standen display='summary'> </dzs-standen>
```

<img width="360" height="483" alt="image" src="https://github.com/user-attachments/assets/1571963b-db10-41f2-bd24-871a7e2ec90c" /><br>

**Overview standings 1e klasse:**

```html
<dzs-standen display='overview' klasse='1e'> </dzs-standen>
```

<img width="1087" height="305" alt="image" src="https://github.com/user-attachments/assets/914b3a3a-de72-4620-a856-54678ae20385" /><br>

**Overview standings 2e klasse:**

```html
<dzs-standen display='overview' klasse='2e'> </dzs-standen>
```
<img width="1087" height="456" alt="image" src="https://github.com/user-attachments/assets/748af68b-a7eb-490b-8be0-1b5730527df4" /><br>

**Overview standings 3e klasse:**

```html
<dzs-standen display='overview' klasse='3e'> </dzs-standen>
```

<img width="1083" height="396" alt="image" src="https://github.com/user-attachments/assets/4315360b-985d-40ed-bd81-cd5ac3d51553" /><br>

**Overview standings 4e klasse:**

```html
<dzs-standen display='overview' klasse='4e'> </dzs-standen>
```

<img width="1085" height="397" alt="image" src="https://github.com/user-attachments/assets/3bf5990f-e865-41d2-b05c-ed1db219c252" /><br>

**Overview standings 5e klasse:**

```html
<dzs-standen display='overview' klasse='5e'> </dzs-standen>
```

<img width="1084" height="456" alt="image" src="https://github.com/user-attachments/assets/58895606-373d-4011-affb-4c759dee2f7d" /><br>

### Wedstrijden/programma/uitslagen:

**Summary of upcomming matches on the homepage**

```html
<dzs-wedstrijden display='summary' status='programma' limit='15'> </dzs-wedstrijden>
```
<img width="366" height="480" alt="image" src="https://github.com/user-attachments/assets/be80cf82-f03f-411c-bf95-ef55cd8e1eba" /><br>

**Summary of played matches on the homepage**

```html
<dzs-wedstrijden display='summary' status='uitslagen' limit='15'> </dzs-wedstrijden>
```

<img width="364" height="481" alt="image" src="https://github.com/user-attachments/assets/2c2b091c-8aaf-4ffd-97ee-f7c8503fd1f2" /><br>

**Overview of all upcomming matches** (including filters):
```html
<dzs-wedstrijden display='overview' status='programma'> </dzs-wedstrijden>
```

<img width="1692" height="1604" alt="image" src="https://github.com/user-attachments/assets/1b182ad5-7e09-4efc-9265-b36f5b75854f" />


**Overview of all played matches** (including filters):
```html
<dzs-wedstrijden display='overview' status='uitslagen'> </dzs-wedstrijden>
```

<img width="1690" height="1569" alt="image" src="https://github.com/user-attachments/assets/46294d4a-548c-40c2-a204-0f9304b4bced" />

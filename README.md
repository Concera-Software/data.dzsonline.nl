# data.dzsonline.nl

## Import

https://data.dzsonline.nl/import.php

## Webcomponents

**Include JavaScript:**
```html
<script src='dzs-webcomponents.js' type='text/javascript'></script>
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
<dzs-wedstrijden display='summary' status='gespeeld' limit='15'> </dzs-wedstrijden>
```
<img width="366" height="480" alt="image" src="https://github.com/user-attachments/assets/be80cf82-f03f-411c-bf95-ef55cd8e1eba" /><br>

**Summary of played matches on the homepage**

```html
<dzs-wedstrijden display='summary' status='gepland' limit='15'> </dzs-wedstrijden>
```

<img width="364" height="481" alt="image" src="https://github.com/user-attachments/assets/2c2b091c-8aaf-4ffd-97ee-f7c8503fd1f2" /><br>

**Overview of all upcomming matches** (including filters):
```html
<dzs-wedstrijden display='overview' status='gepland'> </dzs-wedstrijden>
```

<img width="1083" height="1031" alt="image" src="https://github.com/user-attachments/assets/e0c48a74-af57-4471-9db8-ef6964fda0dd" /><br>

**Overview of all played matches** (including filters):
```html
<dzs-wedstrijden display='overview' status='gespeeld'> </dzs-wedstrijden>
```

<img width="1086" height="1048" alt="image" src="https://github.com/user-attachments/assets/9167aae9-dd5b-4c31-a3d8-ce64d83c2382" />

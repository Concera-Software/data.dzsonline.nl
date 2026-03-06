# data.dzsonline.nl

## Import

## Webcomponents

**Include JavaScript:
```html
<script src='dzs-webcomponents.js' type='text/javascript'></script>
```

### Standen/teams:

**On the homepage**

```html
<dzs-standen display='summary'> </dzs-standen>
```

**On https://dzsonline.nl/klase/ or https://dzsonline.nl/standen:**

```html
<dzs-standen display='overview' klasse='1e'> </dzs-standen>
```

```html
<dzs-standen display='overview' klasse='2e'> </dzs-standen>
```

```html
<dzs-standen display='overview' klasse='3e'> </dzs-standen>
```

```html
<dzs-standen display='overview' klasse='4e'> </dzs-standen>
```

```html
<dzs-standen display='overview' klasse='5e'> </dzs-standen>
```

### Wedstrijden/programma/uitslagen:

**On the homepage**

```html
<dzs-wedstrijden display='summary' status='gepland' limit='15'> </dzs-wedstrijden>
```

```html
<dzs-wedstrijden display='summary' status='gespeeld' limit='15'> </dzs-wedstrijden>
```

**On https://dzsonline.nl/programma/:**
```html
<dzs-wedstrijden display='overview' status='gepland'> </dzs-wedstrijden>
```

**On https://dzsonline.nl/uitslagen/:**
```html
<dzs-wedstrijden display='overview' status='gespeeld'> </dzs-wedstrijden>
```
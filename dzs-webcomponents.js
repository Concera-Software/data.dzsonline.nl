class dzsData
{
	constructor(url)
	{
		this.url = url;
		this.cache = null; // Hier bewaren we de data als cache
    		this.fetchPromise = null; // Houdt de lopende fetch Promise bij	

		this.klasseNames = {};
		this.teamNames = {};
		this.zaalNames = {};
	}

	async getData()
	{
		if (this.cache)
		{
			return this.cache; // Return de eerder opgehaalde data
		}

		if (this.fetchPromise) {
			return this.fetchPromise; // Wacht op de lopende fetch
		}

		this.fetchPromise = fetch(this.url)
		.then(response => {
			if (!response.ok)
			{
				throw new Error('Netwerkfout: ' + response.statusText);
			}
			return response.json();
		})
		.then(data => {
			this.cache = data;      // Cache vullen
			this.fetchPromise = null; // Reset de fetchPromise
			return data;
		})
		.catch(error => {
			this.fetchPromise = null; // Reset bij fout
			throw error;
		});

		return this.fetchPromise;
	}

	getKlasseName(id)
	{
		if(id in this.klasseNames)
		{
			return this.klasseNames[id];
		}
		
		let klasseName = null;
		this.cache['klassen']['data'].forEach(klasse => {
			if(klasse['id'] == id)
			{
				klasseName = klasse['name'];
			}
		});

		if(klasseName != null)
		{
			this.klasseNames[id] = klasseName;
		}

		return klasseName;
	}

	getTeamName(id)
	{
		if(id in this.teamNames)
		{
			return this.teamNames[id];
		}
		
		let teamName = null;
		this.cache['klassen']['data'].forEach(klasse => {
			if(klasse['teams']['meta']['count'] == 0)
			{
				return;
			}
			
			klasse['teams']['data'].forEach(team => {
				if(team['id'] == id)
				{
					teamName = team['name'];
				}
			});
		});

		if(teamName != null)
		{
			this.teamNames[id] = teamName;
		}

		return teamName;
	}

	getZaalName(id)
	{
		if(id in this.zaalNames)
		{
			return this.zaalNames[id];
		}
		
		let zaalName = null;
		this.cache['zalen']['data'].forEach(zaal => {
			if(zaal['id'] == id)
			{
				zaalName = zaal['name'];
			}
		});

		if(zaalName != null)
		{
			this.zaalNames[id] = zaalName;
		}

		return zaalName;
	}
}

// Gebruik voorbeeld:
const dataInstance = new dzsData('//data.dzsonline.nl/data.json');

class dzsStanden extends HTMLElement
{
	constructor()
	{
		super();
    		this.attachShadow({ mode: 'open' }); // Shadow DOM voor encapsulatie
		// element created
	}

	connectedCallback()
	{
		// browser calls this method when the element is added to the document
		// (can be called many times if an element is repeatedly added/removed)
		this.renderLoading();
		dataInstance.getData()
			.then(data => this.renderData(data))
			.catch(err => this.renderError(err));
	}

	renderLoading() {
		this.shadowRoot.innerHTML = `
			<style>
				.loading { color: gray; font-style: italic; }
			</style>
			<div class="loading">Gegevens worden geladen...</div>
		`;
	}

	renderData(data) {

		let attrDisplay = this.getAttribute('display');
		let attrKlasse = this.getAttribute('klasse');

		let title = 'STANDEN';

		let template = `
			<style>
				p
				{
					margin-top: 0px;
					text-transform: uppercase;
				}
			
				table.teams
				{
					width: 100%;
					border-collapse: collapse;
				}
			
				table.teams thead th,
				table.teams tbody td
				{
					white-space: nowrap;
					padding: 3px;
				}

				table.teams thead th p
				{
					font-weight: normal;
					margin-top: -3px;
				}

				table.teams tbody tr:nth-child(odd)
				{
					background-color: #E6E6E6;
				}

				.clamp
				{
					display: -webkit-box;
					-webkit-line-clamp: 1; /* breek na 1 regel */
					-webkit-box-orient: vertical;
					overflow: hidden;
					text-overflow: ellipsis; /* optioneel: toont ... */
					white-space: normal; /* laat regelafbreking toe */
				}				
			</style>`;

		if(attrDisplay == 'summary')
		{	
			template += `<table class='teams'>
				<thead>
					<tr>
						<th colspan="2" style='text-align: left'>
							<p><span style="color: #ff0000;"><strong>${title}</strong></span></p>
						</th>
						<th><p><span><strong>+</strong></span></p></th>
						<th><p><span><strong>-</strong></span></p></th>
						<th><p><span><strong>Pt.</strong></span></p></th>
					</tr>
				</thead>
				<tbody>`;

			data['klassen']['data'].forEach(klasse => {

				// Skip the klasse when no teams are found
				//
				if(klasse['teams']['meta']['count'] == 0)
				{
					return;
				}
				
				// Get the first ranked team from the klasse
				//
				let team = klasse['teams']['data'][0];
				if(team['punten'] == null)
				{
					return;
				}

				template += `<tr id='dzs-team-${team['id']}'>
					<td style='width: 30px'>${klasse['name']}</td>
					<td><div class="clamp">${team['name']}</div></td>
					<td style='width: 30px' align="right">${team['puntenVoor']}</td>
					<td style='width: 30px' align="right">${team['puntenTegen']}</td>
					<td style='width: 30px' align="right">${team['punten']}</td>
				</tr>`;
			});
		}
		else
		{
			data['klassen']['data'].forEach(klasse => {
				if(klasse['teams']['meta']['count'] == 0)
				{
					return;
				}

				if(klasse['name'] == attrKlasse)
				{
					template += `<p><span style="color: #ff0000;"><strong>${klasse['name']}</strong></span></p>
					<table class='teams'>
					<thead>
						<tr>
							<th></th>
							<th>Team</th>
							<th>Gespeeld</th>
							<th>Gewonnen</th>
							<th>Gelijk</th>
							<th>Verloren</th>
							<th>Punten</th>
							<th>Voor</th>
							<th>Tegen</th>
						</tr>
					</thead>
					<tbody>`;
					
					klasse['teams']['data'].forEach(team => {
						template += `<tr id='dzs-team-${team['id']}'>
							<td style='width: 30px'>${team['positie']}</td>
							<td>${team['name']}</td>
							<td style='width: 75px' align="center">${team['gespeeld']}</td>
							<td style='width: 75px' align="center">${team['gewonnen']}</td>
							<td style='width: 75px' align="center">${team['gelijk']}</td>
							<td style='width: 75px' align="center">${team['verloren']}</td>
							<td style='width: 75px' align="center">${team['punten']}</td>
							<td style='width: 75px' align="center">${team['puntenVoor']}</td>
							<td style='width: 75px' align="center">${team['puntenTegen']}</td>
						</tr>`;
					});
				
					template += `</tbody>
					</table>`;
				}
			});
		}

		this.shadowRoot.innerHTML = template;
	};

	renderError(error)
	{
		this.shadowRoot.innerHTML = `
			<style>
				.error { color: red; font-weight: bold; }
			</style>
			<div class="error">Fout bij laden data: ${error.message}</div>
		`;
	}

	disconnectedCallback()
	{
		// browser calls this method when the element is removed from the document
		// (can be called many times if an element is repeatedly added/removed)
	}

	static get observedAttributes()
	{
		return ['display', 'klasse'];
	}

	attributeChangedCallback(name, oldValue, newValue)
	{
		// called when one of attributes listed above is modified
	}

	adoptedCallback()
	{
		// called when the element is moved to a new document
		// (happens in document.adoptNode, very rarely used)
	}

	// there can be other element methods and properties
}

// let the browser know that <dzs-standen> is served by our new class
customElements.define("dzs-standen", dzsStanden);

class dzsWedstrijden extends HTMLElement
{
	wedstrijdLijst = [];
	wedstrijdFilters = {};

	constructor()
	{
		super();
    		this.attachShadow({ mode: 'open' }); // Shadow DOM voor encapsulatie
		// element created
	}

	connectedCallback()
	{
		// browser calls this method when the element is added to the document
		// (can be called many times if an element is repeatedly added/removed)
		this.renderLoading();
		dataInstance.getData()
			.then(data => this.renderData(data))
			.catch(err => this.renderError(err));
	}

	renderLoading() {
		this.shadowRoot.innerHTML = `
			<style>
				.loading { color: gray; font-style: italic; }
			</style>
			<div class="loading">Gegevens worden geladen...</div>
		`;
	}

	getWestrijdStatus()
	{
		let attrStatus = this.getAttribute('status');

		switch(attrStatus)
		{
			case '1':
			case 'gespeeld':
			case 'uitslagen':
				return 1;
				break;

			case '2':
			case 'gepland':
			case 'programma':
				return 2;
				break;
		}

		return 0;
	}

	/**
	 * Checks if the given date has to be displayed, based on the 'webstrijdstatus'. When the
	 * wedstrijdStatus is 1, this means only dates that are passes/played/completed are allowed.
	 * When the wedstrijdStatus is 2, this means only matches/dates that are upcoming/schedules
	 * are allowed. 
	 * 
	 * @param {*} date 
	 * @returns 
	 */
	checkDate(date)
	{

		let now = new Date();
		let input = new Date(date);
		let wedstrijdStatus = this.getWestrijdStatus();

		if(wedstrijdStatus == 1)
		{
			return (input <= now);
		}
		else if(wedstrijdStatus == 2)
		{
			return (input > now);
		}

		// By default, return true. When no 'wedstrijdStatus' is specified, always allow the
		// date.
		//
		return true;
	}

	renderData(data) {

		let attrDisplay = this.getAttribute('display');
		let attrLimit = parseInt(this.getAttribute('limit'));

		let title = 'WEDSTRIJDEN';
		if(this.getWestrijdStatus() == 1)
		{
			title = 'UITSLAGEN';
		}
		else if(this.getWestrijdStatus() == 2)
		{
			title = 'PROGRAMMA';
		}

		let template = `
			<style>
				p
				{
					margin-top: 0px;
				}
			
				table.filters,
				table.wedstrijden
				{
					width: 100%;
					border-collapse: collapse;
				}
			
				table.filters tbody td select
				{
					width: 100%;
					padding: 2px;
				}
			
				table.filters tbody td button
				{
					width: 100%;
					border: 1px solid #000;
					background-color: #FE0000;
					color: #FFF;
					cursor: pointer;
					padding: 6px;
				}

				table.wedstrijden thead th,
				table.wedstrijden tbody td
				{
					white-space: nowrap;
				}
				
				table.wedstrijden thead th
				{
					text-align: left;
				}
				table.wedstrijden tbody tr:not(.even):nth-child(odd),
				table.wedstrijden tbody tr.odd
				{
					background-color: #E6E6E6;
				}
				table.wedstrijden font.shortText
				{
					display: none;
				}

				/* On small screens, hide 2nd and 3rd columns */
				@media (max-width: 950px)
				{
					table.wedstrijden thead th.zaaldienst,
					table.wedstrijden tbody td.zaaldienst
					{
						display: none;
					}
				}
				
				@media (max-width: 850px)
				{
					table.wedstrijden font.longText
					{
						display: none;
					}
					
					table.wedstrijden font.shortText
					{
						display: block;
					}
				}
				
				@media (max-width: 800px)
				{
					table.wedstrijden thead th.zaal,
					table.wedstrijden tbody td.zaal
					{
						display: none;
					}
				}
				
				@media (max-width: 750px)
				{
					table.wedstrijden thead th.klasse,
					table.wedstrijden tbody td.klasse
					{
						display: none;
					}

					table.wedstrijden.UITSLAGEN thead th.tijd,
					table.wedstrijden.UITSLAGEN tbody td.tijd
					{
						display: none;
					}
				}

				.clamp
				{
					display: -webkit-box;
					-webkit-line-clamp: 1; /* breek na 1 regel */
					-webkit-box-orient: vertical;
					overflow: hidden;
					text-overflow: ellipsis; /* optioneel: toont ... */
					white-space: normal; /* laat regelafbreking toe */
				}
			</style>`;

		if(attrDisplay == 'summary')
		{
			let rows = 0;
			template += `<p><span style="color: #ff0000;"><strong>${title}</strong></span></p> 
			<table class='wedstrijden'>
				<thead>
				</thead>
				<tbody>`;

			let wedstrijdLijst = data['wedstrijden']['data'];
			if(this.getWestrijdStatus() == 1)
			{
				wedstrijdLijst = wedstrijdLijst.slice().reverse();
			}

			wedstrijdLijst.forEach(wedstrijd => {
				if(!isNaN(attrLimit) && (rows > attrLimit))
				{
					return;
				}

				if(!this.checkDate(wedstrijd['datumTijd']))
				{
					return;
				}

				template += `<tr id='dzs-game-${wedstrijd['id']}'>
					<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamThuis'])}</div></td>
					<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamUit'])}</div></td>`
				
				if(this.getWestrijdStatus() == 1)
				{
					if((wedstrijd['doelpuntenTeamThuis'] != null) && (wedstrijd['doelpuntenTeamUit'] != null))
					{
						template += `<td class='datumText'>${wedstrijd['doelpuntenTeamThuis']} - ${wedstrijd['doelpuntenTeamUit']}</td>`;
					}
					else
					{
						template += `<td class='datumText'><i style='color: gray'>n.n.b.</i></td>`;
					}
				}
				else if(this.getWestrijdStatus() == 2)
				{
					template += `<td class='datumText'>${wedstrijd['datumText']}</td>`;
				}
				else
				{
					template += `<td></td>`;
				}

				template += `</tr>`;
				rows++;
			});
		}
		else
		{
			let klassen = [];
			let teams = [];
			let wedstrijdDagen = [];
			this.wedstrijdLijst = [];
			let zalen = [];

			data['klassen']['data'].forEach(klasse => {
				if(klasse['teams']['meta']['count'] == 0)
				{
					return;
				}

				klassen.push({'value': klasse['id'], 'name': klasse['name']});

				klasse['teams']['data'].forEach(team => {
					teams.push({'value': team['id'], 'name': team['name'] + ' ('+klasse['name']+')'});
				});
			});

			data['wedstrijden']['data'].forEach(wedstrijd => {
				if(this.checkDate(wedstrijd['datumTijd']))
				{
					this.wedstrijdLijst.push(wedstrijd)
				}
			});

			data['wedstrijddagen']['data'].forEach(wedstrijdDag => {
				if(this.checkDate(wedstrijdDag['value']))
				{
					wedstrijdDagen.push({'value': wedstrijdDag['value'], 'name': wedstrijdDag['name']});
				}
			});

			data['zalen']['data'].forEach(zaal => {
				zalen.push({'value': zaal['id'], 'name': zaal['name']});
			});

			// Sorteer teams op naam
			//
			teams.sort((a, b) => a.name.localeCompare(b.name));

			// Inverteer wedstrijddagen
			if(this.getWestrijdStatus() == 1)
			{
				wedstrijdDagen = wedstrijdDagen.slice().reverse();
				this.wedstrijdLijst = this.wedstrijdLijst.slice().reverse();
			}

			template += `<p><span style="color: #ff0000;"><strong>${title}</strong></span></p>
			<table class='filters'>
				<tbody>
					<tr>
						<td width="185">Datum:</td><td width="500">
							<select class="dzs_select" name="datum" on>
								<option value='' selected>-</option>`;
								wedstrijdDagen.forEach(option => {
									template += `<option value ='${option.name}'>${option.name}</option>`;
								});
							template += `</select>
						</td>
					</tr>
					<tr>
						<td>Zaal:</td>
						<td>
							<select class="dzs_select" name="idZaal">
								<option value='' selected>-</option>`;
							zalen.forEach(option => {
								template += `<option value ='${option.value}'>${option.name}</option>`;
							});
							template += `</select>
						</td>
					</tr>
					<tr>
						<td>Klasse:</td>
						<td>
							<select class="dzs_select" name="idKlasse">
								<option value='' selected>-</option>`;
							klassen.forEach(option => {
								template += `<option value ='${option.value}'>${option.name}</option>`;
							});
							template += `</select>
						</td>
					</tr>
					<tr>
						<td>Team:</td>
						<td>
							<select class="dzs_select" name="idTeam">
								<option value='' selected>-</option>`;
							teams.forEach(option => {
								template += `<option value ='${option.value}'>${option.name}</option>`;
							});
							template += `</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<button class="dzs_button">Toon alle ${this.wedstrijdLijst.length} wedstrijden / wis filters</button>
						</td>
					</tr>
				</tbody>
			</table><br><br>`;

			template += `<table class='wedstrijden ${title}'>
				<thead>
					<tr>
						<th class='zaal'>Zaal</th>
						<th class='datum'><font class='shortText'>Dag</font><font class='longText'>Datum</font></th>
						<th class='tijd'><font class='shortText'>Tijd</font><font class='longText'>Aanvang</font></th>
						<th class='klasse'>Kls.</th>
						<th>Team Thuis</th>
						<th>Team Uit</th>`;

						if(this.getWestrijdStatus() == 1)
						{
							template += `<th class='uitslag'>Uitslag</th>`;
						}
						else if(this.getWestrijdStatus() == 2)
						{
							template += `<th class='zaaldienst'>Zaaldienst</th>`;
						}
						else
						{
							template += `<th></th>`; // ??
						}
					template += `<tr>
				</thead>
				<tbody>`;

				this.wedstrijdLijst.forEach(wedstrijd => {

					template += `<tr id='dzs-game-${wedstrijd['id']}'>
						<td class='zaal'>${dataInstance.getZaalName(wedstrijd['idZaal'])}</td>
						<td class='datum'><font class=\'shortText\'>${wedstrijd['datum'].substr(0, 5)}&nbsp;</font><font class=\'longText\'>${wedstrijd['datum']}</font></td>
						<td class='tijd'>${wedstrijd['tijd'].substr(0, 5)}&nbsp;</td>
						<td class='klasse'>${dataInstance.getKlasseName(wedstrijd['idKlasse'])}</td>
						<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamThuis'])}</div></td>
						<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamUit'])}</div></td>`;
					
					if(this.getWestrijdStatus() == 1)
					{
						if((wedstrijd['doelpuntenTeamThuis'] != null) && (wedstrijd['doelpuntenTeamUit'] != null))
						{
							template += `<td class='uitslag'>${wedstrijd['doelpuntenTeamThuis']} - ${wedstrijd['doelpuntenTeamUit']}</td>`;
						}
						else
						{
							template += `<td class='uitslag'><i style='color: gray'>n.n.b.</i></td>`;
						}
					}
					else if(this.getWestrijdStatus() == 2)
					{
						template += `<td class='zaaldienst'><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst'])}</div></td>`;
					}
					else
					{
						template += `<td></td>`;
					}

					template += `</tr>`;
				});

				template += `</tbody>
			</table>`;
		}

		this.shadowRoot.innerHTML = template;

		if(attrDisplay == 'overview')
		{
			let selectElements = this.shadowRoot.querySelectorAll('select');
			selectElements.forEach(select => {
				select.addEventListener('change', this.handleChange.bind(this));
			});

			let buttonElement = this.shadowRoot.querySelector('button');
			buttonElement.addEventListener('click', this.clearFilters.bind(this));
		}
	};

	renderError(error)
	{
		this.shadowRoot.innerHTML = `
			<style>
				.error { color: red; font-weight: bold; }
			</style>
			<div class="error">Fout bij laden data: ${error.message}</div>
		`;
	}

	handleChange(event) {
		const selectName = event.target.name;
		const selectedValue = event.target.value;

		if(selectedValue !== '')
		{
			this.wedstrijdFilters[event.target.name] = event.target.value;
		}
		else if(event.target.name in this.wedstrijdFilters)
		{
			delete this.wedstrijdFilters[event.target.name];
		}

		// console.log(`Selectie gewijzigd in ${selectName}: ${selectedValue}`);

		this.filterWedstrijden();
	}

	clearFilters() {
	
		let selectElements = this.shadowRoot.querySelectorAll('select');
		selectElements.forEach(select => {
			select.value = '';
		});

		this.wedstrijdFilters = {};
		this.filterWedstrijden();
	}

	filterWedstrijden()
	{	
		let visibleIndex = 0;
		this.wedstrijdLijst.forEach(wedstrijd => {
			
			let row = this.shadowRoot.querySelector('#dzs-game-'+wedstrijd['id']);
			if(!this.wedstrijdMatchesFilter(wedstrijd))
			{
				row.hidden = true; // sets the hidden property to true (hides the row)
			}
			else
			{
				row.removeAttribute('hidden'); // removes the hidden attribute
				row.classList.remove('odd', 'even');
				row.classList.add(++visibleIndex % 2 === 0 ? 'even' : 'odd');
			}
		});
	}

	wedstrijdMatchesFilter(wedstrijd)
	{
		let countFilters = Object.keys(this.wedstrijdFilters).length;
		let countMatches = 0;

		for (const [key, value] of Object.entries(this.wedstrijdFilters))
		{
			if(value == '')
			{
				return;
			}

			switch(key)
			{
				case 'idTeam':
					if(
						(wedstrijd['idTeamThuis'] == value)
						|| (wedstrijd['idTeamUit'] == value)
						|| (wedstrijd['idTeamZaalDienst'] == value)
					)
					{
						countMatches++;
					}	
					break;

				default:
					if(wedstrijd[key] == value)
					{
						countMatches++;
					}
			}
		};

		return (countMatches >= countFilters);
	}

	disconnectedCallback()
	{
		// browser calls this method when the element is removed from the document
		// (can be called many times if an element is repeatedly added/removed)
	}

	static get observedAttributes()
	{
		return ['display', 'status', 'limit'];
	}

	attributeChangedCallback(name, oldValue, newValue)
	{
		// called when one of attributes listed above is modified
	}

	adoptedCallback()
	{
		// called when the element is moved to a new document
		// (happens in document.adoptNode, very rarely used)
	}

	// there can be other element methods and properties
}

// let the browser know that <dzs-wedstrijden> is served by our new class
customElements.define("dzs-wedstrijden", dzsWedstrijden);
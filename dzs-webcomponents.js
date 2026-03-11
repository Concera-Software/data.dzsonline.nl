/**
 * -- FILEDESCRIPTION:
 *
 * This file contains the webcomponents dzs-standen and dzs-wedstrijden, which can be placed as
 * HTML-elements into an existing webpage, used to display the data fetched from data.dzsonline.nl.
 */

/**
 * The dzsData class will be used by both webcomponents in order to fetch the data from the URL at
 * data.dzsonline.nl/data.json, by calling function getData(). When parameter cache is set to true
 * (default) and variable this.cache already contains data, the cached result will be returned. When
 * a request is already busy, the fetchPromise will be returned instead to prevent calling the same
 * url again multiple times.
 */
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

	async getData(cached=true)
	{
		// Return cached data if available
		//
		if (cached && (this.cache != null))
		{
			return this.cache; // Return de eerder opgehaalde data
		}

		// Return the fetchPromise, while a request is already busy...
		if (this.fetchPromise != null)
		{
			return this.fetchPromise;
		}

		// Execute the request, store the fetchPromise while it's busy and clear is at the
		// end. When the response was ok, convert is to JSON.
		//
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

		//
		return this.fetchPromise;
	}

	/**
	 * Because the data in key 'wedstrijden' from the API request only contains id's for the
	 * 'klasse' of a 'wedstrijd' (not the name itself), this function can be used to lookup the
	 * name instead.
	 *
	 * To prevent looping through the whole data each time this function is called, we'll store
	 * the result (name of the 'klasse') of this function in this.klasseNames, based on the
	 * given id. When this function is called with the same id again, the cached value from
	 * variable this.klasseNames will be returned (if found).
	 *
	 * @param {int} id  The id of the 'klasse' to get the name for.
	 * @returns         The name of the 'klasse' (if found).
	 */
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

	/**
	 * Because the data in key 'wedstrijden' from the API request only contains id's for the
	 * 'team' of a 'wedstrijd' (not the name itself), this function can be used to lookup the
	 * name instead.
	 *
	 * To prevent looping through the whole data each time this function is called, we'll store
	 * the result (name of the 'team') of this function in this.teamNames, based on the given
	 * id. When this function is called with the same id again, the cached value from variable
	 * this.teamNames will be returned (if found).
	 *
	 * In order to loop up a team, we'll first loop through the 'klassen' element in the data,
	 * followed by looping through all teams (if available) in the 'teams' element of each
	 * 'klasse'.
	 *
	 * @param {int} id  The id of the 'team' to get the name for.
	 * @returns         The name of the 'team' (if found).
	 */
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

	/**
	 * Because the data in key 'wedstrijden' from the API request only contains id's for the
	 * 'zaal' of a 'wedstrijd' (not the name itself), this function can be used to lookup the
	 * name instead.
	 *
	 * To prevent looping through the whole data each time this function is called, we'll store
	 * the result (name of the 'zaal') of this function in this.zaalNames, based on the given
	 * id. When this function is called with the same id again, the cached value from variable
	 * this.zaalNames will be returned (if found).
	 *
	 * @param {int} id  The id of the 'zaal' to get the name for.
	 * @returns         The name of the 'zaal' (if found).
	 */
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

// Create the dataInstance, used to fetch the data from data.dzsonline.nl and cache the result, so
// when the same data is requested by multiple webcomponents at the same time, only one request will
// be executed.
//
const dataInstance = new dzsData('//data.dzsonline.nl/data.json');

// Create the class dzsStanden for webcomponent <dzs-standen>. This webcomponent will fetch the data
// from data.dzsonline.nl using the dataInstance and use the result to generate the HTML-template
// for the component, using attribute "display='summary'" or "display='overview'" to show a short
// summary of the 'standen' (only the top team for each 'klasse') or a full overview for the
// specified 'klasse', using attribue "klasse='...'"
//
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

	// Creates/displays a loader to indicate the data is being loaded...
	//
	renderLoading() {
		this.shadowRoot.innerHTML = `
			<style>
				.loading { color: gray; font-style: italic; }
			</style>
			<div class="loading">Gegevens worden geladen...</div>
		`;
	}

	// Creates/displays an error to indicate something went wrong...
	//
	renderError(error)
	{
		this.shadowRoot.innerHTML = `
			<style>
				.error { color: red; font-weight: bold; }
			</style>
			<div class="error">Fout bij laden data: ${error.message}</div>
		`;
	}

	renderData(data) {

		// Get the values for attibutes 'display' and 'klasse' from the HTML-element, so
		// we'll know which data to display.
		//
		let attrDisplay = this.getAttribute('display');
		let attrKlasse = this.getAttribute('klasse');
		let title = 'STANDEN';

		// Start the template by adding inline css/stylesheets for the webcomponents. In
		// this case, we'll add some styles for the table with class 'teams', used to style
		// the headers and add some colors for odd/even rows.
		//
		// Using the @media query, additional styles are added for smaller displays, used to
		// hide some texts/columns from the table, so even on mobile phones, the table is
		// displayed correctly.
		//
		let template = `
			<style>
				p
				{
					margin-top: 0px;
					text-transform: uppercase;
				}
			
				/* Style table */
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
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 75px;
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
				table.teams font.shortText
				{
					display: none;
				}

				/* Use a smaller font (14px) and padding (0.9px) and narrow the
				 * columns with points and amount of matches to 70px on screens
				 * smaller then 1024px, so the full table remains visible. Also. On
				 * mobile devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 1024px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 14px;
						padding: 0.9px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 70px;
					}
				}

				/* Use an even smaller font (13px) and narrow the columns with
				 * points and amount of matches to 65px on screens smaller then
				 * 960px, so the full table remains visible. Also. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 960px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 13px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 65px;
					}
				}

				/* Use an even smaller font (12px) and padding (0.8px) and narrow
				 * the columns with points and amount of matches to 60px on screens
				 * smaller then 920px, so the full table remains visible. Also. On
				 * mobile devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 920px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 12px;
						padding: 0.8px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 60px;
					}
				}

				/* Use an even smaller font (11px) and narrow the columns with
				 * points and amount of matches to 55px on screens smaller then
				 * 860px, so the full table remains visible. Also. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 860px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 11px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 55px;
					}
				}

				/* Use an even smaller font (10px) and padding (0.7px) and narrow
				 * the columns with points and amount of matches to 50px on screens
				 * smaller then 800px, so the full table remains visible. Also. On
				 * mobile devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 800px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 10px;
						padding: 0.7px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 50px;
					}
				}

				/* Use an even smaller font (9px) and narrow the columns with
				 * points and amount of matches to 45px on screens smaller then
				 * 720px, so the full table remains visible. Also. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 720px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: ;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 45px;
					}
				}

				/* Use an even smaller font (8px) and padding (0.6px) and narrow
				 * the columns with points and amount of matches to 40px on screens
				 * smaller then 660px, so the full table remains visible. Also. On
				 * mobile devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 660px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 8px;
						padding: 0.6px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 40px;
					}
				}

				/* Use an even smaller font (7px) and narrow the columns with
				 * points and amount of matches to 35px on screens smaller then
				 * 600px, so the full table remains visible. Also. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 600px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 7px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 35px;
					}
				}

				/* Use an even smaller font (6px) and padding (0.5px) and narrow
				 * the columns with points and amount of matches to 30px on screens
				 * smaller then 540px, so the full table remains visible. Also. On
				 * mobile devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 540px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 6px;
						padding: 0.5px;
					}
			
					table.teams thead th.gespeeld,
					table.teams tbody td.gespeeld,
					table.teams thead th.gewonnen,
					table.teams tbody td.gewonnen,
					table.teams thead th.gelijk,
					table.teams tbody td.gelijk,
					table.teams thead th.verloren,
					table.teams tbody td.verloren,
					table.teams thead th.points,
					table.teams tbody td.points
					{
						width: 30px;
					}
				}

				/* Use an even smaller font (5px) on screens smaller then 500px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text.
				 */
				@media (max-width: 500px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 5px;
					}
				}

				/* Use an even smaller font (4px) and padding (0.4px) on screens
				 * smaller then 420px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 420px)
				{
					table.teams thead th,
					table.teams tbody td
					{
						font-size: 4px;
						padding: 0.4px;
					}
				}

				/**
				 * Class .clamp can be used inside table-cells to reduce/shrink the
				 * text in the cell as the table gets smaller.
				 */
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

		// When attribute display is set to 'summary', only show a table with only the
		// first team in each 'klasse' and the amount of points
		//
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
				let firstTeam = klasse['teams']['data'][0];
				if(firstTeam['punten'] == null)
				{
					return;
				}

				template += `<tr id='dzs-team-${firstTeam['id']}'>
					<td style='width: 30px'>${klasse['name']}</td>
					<td><div class="clamp">${firstTeam['name']}</div></td>
					<td style='width: 30px' align="right">${firstTeam['puntenVoor']}</td>
					<td style='width: 30px' align="right">${firstTeam['puntenTegen']}</td>
					<td style='width: 30px' align="right">${firstTeam['punten']}</td>
				</tr>`;
			});
		}

		// Otherwise (when attribute display is set to 'overview'), use attribute 'klasse'
		// to find the specified classe and create a table with all teams, ranked based on
		// their position and detailed information about the amount of 'wedstrijden' that
		// are played, won, lost, total points etc.
		//
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
							<th class='gespeeld'>Gespeeld</th>
							<th class='gewonnen'>Gewonnen</th>
							<th class='gelijk'>Gelijk</th>
							<th class='verloren'>Verloren</th>
							<th class='points'><font class='shortText'>+</font><font class='longText'>Voor</font></th>
							<th class='points'><font class='shortText'>-</font><font class='longText'>Tegen</font></th>
							<th class='points'><font class='shortText'>Pt.</font><font class='longText'>Punten</font></th>
						</tr>
					</thead>
					<tbody>`;
					
					klasse['teams']['data'].forEach(team => {
						template += `<tr id='dzs-team-${team['id']}'>
							<td style='max-width: 30px'>${team['positie']}</td>
							<td><div class="clamp">${team['name']}</div></td>
							<td class='gespeeld' align="center">${team['gespeeld']}</td>
							<td class='gewonnen' align="center">${team['gewonnen']}</td>
							<td class='gelijk' align="center">${team['gelijk']}</td>
							<td class='verloren' align="center">${team['verloren']}</td>
							<td class='points' align="center">${team['puntenVoor']}</td>
							<td class='points' align="center">${team['puntenTegen']}</td>
							<td class='points' align="center">${team['punten']}</td>
						</tr>`;
					});
				
					template += `</tbody>
					</table>`;
				}
			});
		}

		this.shadowRoot.innerHTML = template;
	};

	disconnectedCallback()
	{
		// browser calls this method when the element is removed from the document
		// (can be called many times if an element is repeatedly added/removed)
	}

	attributeChangedCallback(name, oldValue, newValue)
	{
		// called when one of the observed attributes listed below are modified
	}

	adoptedCallback()
	{
		// called when the element is moved to a new document
		// (happens in document.adoptNode, very rarely used)
	}

	static get observedAttributes()
	{
		return ['display', 'klasse'];
	}

	// there can be other element methods and properties
}

// Let the browser know that <dzs-standen> is served by class dzsStanden
//
customElements.define("dzs-standen", dzsStanden);

class dzsWedstrijden extends HTMLElement
{
	wedstrijden = [];
	wedstrijdFilters = {};

	constructor()
	{
		super();
		this.attachShadow({ mode: 'open' }); // Shadow DOM voor encapsulatie
		// element created
	}

	// Creates/displays a loader to indicate the data is being loaded...
	//
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

	// Creates/displays an error to indicate something went wrong...
	//
	renderError(error)
	{
		this.shadowRoot.innerHTML = `
			<style>
				.error { color: red; font-weight: bold; }
			</style>
			<div class="error">Fout bij laden data: ${error.message}</div>
		`;
	}

	renderData(data) {

		// Get the values for attibutes 'display', 'status' and 'limit' from the HTML-
		// element, so we'll know which data to display.
		//
		let attrDisplay = this.getAttribute('display');
		let attrStatus = this.getAttribute('status');
		let attrLimit = parseInt(this.getAttribute('limit'));

		// Set the title for the webcomponent based on the 'webdstrijdStatus', based on
		// attribute 'status'. When the 'wedstrijdStatus' is set to 1, this means we'll need
		// to display games that are played, so we'll use title 'UITSLAGEN'. When the
		// 'wedstrijdStatus' is set to 2, this means we'll need to display games that are
		// not yet played, so we'll use title 'PROGRAMMA'. When no 'wedstrijdStatus' is
		// specified, the general title 'WEDSTRIJDEN' will be used.
		//
		let title = 'WEDSTRIJDEN';
		if(this.getWestrijdStatus() == 1)
		{
			title = 'UITSLAGEN';
		}
		else if(this.getWestrijdStatus() == 2)
		{
			title = 'PROGRAMMA';
		}

		// Start the template by adding inline css/stylesheets for the webcomponents. In
		// this case, we'll add some styles for the table with class 'filters', used to
		// style the <select> and <button> elemlents in the filters above the full list with
		// 'wedstrijden' and some styles for the table with class 'teams', used to style
		// the headers and add some colors for odd/even rows.
		//
		// Using the @media query, additional styles are added for smaller displays, used to
		// hide some texts/columns from the table, so even on mobile phones, the table is
		// displayed correctly.
		//
		let template = `
			<style>
				p
				{
					margin-top: 0px;
				}
				
				/* Style filters */
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

				/* Style table */
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

				/* Use a smaller font (14px) and padding (0.9px) on screens smaller
				 * then 1024px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 1024px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 14px;
						padding: 0.9px;
					}
				}

				/* Use an even smaller font (13px) on screens smaller then 960px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text.
				 */
				@media (max-width: 960px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 13px;
					}
				}

				/* Use an even smaller font (12px) and padding (0.8px) on screens
				 * smaller then 920px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 920px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 12px;
						padding: 0.8px;
					}
				}

				/* Use an even smaller font (11px) on screens smaller then 860px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text.
				 */
				@media (max-width: 860px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 11px;
					}
				}

				/* Use an even smaller font (10px) and padding (0.7px) on screens
				 * smaller then 920px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 800px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 10px;
						padding: 0.7px;
					}
				}

				/* Use an even smaller font (9px) on screens smaller then 720px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text.
				 */
				@media (max-width: 720px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 9px;
					}
				}

				/* Use an even smaller font (8px) and padding (0.6px) on screens
				 * smaller then 660px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 660px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 8px;
						padding: 0.6px;
					}
				}

				/* Use an even smaller font (7px) on screens smaller then 600px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text
				 */
				@media (max-width: 600px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 7px;
					}
				}

				/* Use an even smaller font (6px) and padding (0.5px) on screens
				 * smaller then 540px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 540px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 6px;
						padding: 0.5px;
					}
				}

				/* Use an even smaller font (5px) on screens smaller then 500px,
				 * so the full table remains visible. On mobile devices, pinch to
				 * zoom has to be used to enlarge the text.
				 */
				@media (max-width: 500px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 5px;
					}
				}

				/* Use an even smaller font (4px) and padding (0.4px) on screens
				 * smaller then 420px, so the full table remains visible. On mobile
				 * devices, pinch to zoom has to be used to enlarge the text.
				 */
				@media (max-width: 420px)
				{
					table.wedstrijden thead th,
					table.wedstrijden tbody td
					{
						font-size: 4px;
						padding: 0.4px;
					}
				}

				/**
				 * Class .clamp can be used inside table-cells to reduce/shrink the
				 * text in the cell as the table gets smaller.
				 */
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

		// When attribute display is set to 'summary', only show a table with the name of
		// the teams and the date (if not played yet) or the score of the game (of played).
		//
		if(attrDisplay == 'summary')
		{
			let rows = 0;
			template += `<p><span style="color: #ff0000;"><strong>${title}</strong></span></p> 
			<table class='wedstrijden'>
				<thead>
				</thead>
				<tbody>`;

			let wedstrijden = data['wedstrijden']['data'];

			// When the 'wedstrijdStatus' is set to 1, this means we'll need to display
			// games that are played, so reverse the list of games so the most recent
			// game will be displayed first.
			//
			if(this.getWestrijdStatus() == 1)
			{
				wedstrijden = wedstrijden.slice().reverse();
			}

			wedstrijden.forEach(wedstrijd => {
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

				// When the 'wedstrijdStatus' is set to 1, this means we'll need to
				// display games that are played, so add the score (if available)
				//
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
				// When the 'wedstrijdStatus' is set to 2, this means we'll need to
				// display games that are not yet played, so add the data of the
				// 'wedstrijd'.
				//
				else if(this.getWestrijdStatus() == 2)
				{
					template += `<td class='datumText'>${wedstrijd['datumText']}</td>`;
				}
				// In all other cases, just add an empty column to prevent the
				// table to break.
				//
				else
				{
					template += `<td></td>`;
				}

				template += `</tr>`;
				rows++;
			});
		}

		// Otherwise (when attribute display is set to 'overview'), the full list of all
		// 'wedstrijden' and some filters shouw be displayed. Bases on attribute 'status',
		// only games that needs to be played or games that still needs to be played will
		// be displayed. Function checkDate() will be used to check if a date from the list
		// of 'wedstrijden' and 'wedstrijdDagen' has to be displayed or not.
		//
		else
		{
			// Loop through the response from the dataInstance and create array for
			// the 'klassen', 'teams', 'wedstrijdDagen', 'wedstrijden' and 'zalen',
			// which will be used to create the filters to search on.
			//
			let klassen = [];
			let teams = [];
			let wedstrijdDagen = [];
			this.wedstrijden = [];
			let zalen = [];

			// Get all 'klassen'
			//
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

			// Get all 'wedstrijden'
			//
			data['wedstrijden']['data'].forEach(wedstrijd => {
				if(this.checkDate(wedstrijd['datumTijd']))
				{
					this.wedstrijden.push(wedstrijd)
				}
			});

			// Get all 'wedstrijddagen'
			//
			data['wedstrijddagen']['data'].forEach(wedstrijdDag => {
				if(this.checkDate(wedstrijdDag['value']))
				{
					wedstrijdDagen.push({'value': wedstrijdDag['value'], 'name': wedstrijdDag['name']});
				}
			});

			// Get all 'zalen'
			//
			data['zalen']['data'].forEach(zaal => {
				zalen.push({'value': zaal['id'], 'name': zaal['name']});
			});

			// Sort teams by name
			//
			teams.sort((a, b) => a.name.localeCompare(b.name));

			// When the 'wedstrijdStatus' is set to 1, this means we'll need to display
			// games that are played, so reverse the list of games so the most recent
			// game will be displayed first.
			//
			if(this.getWestrijdStatus() == 1)
			{
				wedstrijdDagen = wedstrijdDagen.slice().reverse();
				this.wedstrijden = this.wedstrijden.slice().reverse();
			}

			// Create the table for he filters, add a <select>-element and use the
			// created arrays to fill the options for 'wedstrijdDagen', 'zalen',
			// 'klassen' and 'teams'.
			//
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
							<button class="dzs_button">Toon alle ${this.wedstrijden.length} wedstrijden / wis filters</button>
						</td>
					</tr>
				</tbody>
			</table><br><br>`;

			// Create table with all 'wedstrijden' based on the array of data in
			// this.wedstrijden.
			//
			template += `<table class='wedstrijden ${title}'>
				<thead>
					<tr>
						<th class='zaal'>Zaal</th>
						<th class='datum'><font class='shortText'>Dag</font><font class='longText'>Datum</font></th>
						<th class='tijd'><font class='shortText'>Tijd</font><font class='longText'>Aanvang</font></th>
						<th class='klasse'>Kls.</th>
						<th>Team Thuis</th>
						<th>Team Uit</th>`;

						// When the 'wedstrijdStatus' is set to 1, this
						// means we'll need to display games that are
						// played, so add add the header for column
						// 'uitslag'.
						//
						if(this.getWestrijdStatus() == 1)
						{
							template += `<th class='uitslag'>Uitslag</th>`;
						}
						// When the 'wedstrijdStatus' is set to 2, this
						// means we'll need to display games that are noy
						// yet played, so add the header for column for the
						// team that has 'zaaldienst'.
						//
						else if(this.getWestrijdStatus() == 2)
						{
							template += `<th class='zaaldienst'>Team(s) Zaaldienst</th>`;
						}
						// In all other cases, just add an empty column to
						// prevent the table to break.
						//
						else
						{
							template += `<th></th>`; // ??
						}
					template += `<tr>
				</thead>
				<tbody>`;

				this.wedstrijden.forEach(wedstrijd => {

					template += `<tr id='dzs-game-${wedstrijd['id']}'>
						<td class='zaal'>${dataInstance.getZaalName(wedstrijd['idZaal'])}</td>
						<td class='datum'><font class=\'shortText\'>${wedstrijd['datum'].substr(0, 5)}&nbsp;</font><font class=\'longText\'>${wedstrijd['datum']}</font></td>
						<td class='tijd'>${wedstrijd['tijd'].substr(0, 5)}&nbsp;</td>
						<td class='klasse'>${dataInstance.getKlasseName(wedstrijd['idKlasse'])}</td>
						<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamThuis'])}</div></td>
						<td><div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamUit'])}</div></td>`;
					
					// When the 'wedstrijdStatus' is set to 1, this means we'll
					// need to display games that are played, so add the score
					// (if available)
					//
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
					// When the 'wedstrijdStatus' is set to 2, this means we'll
					// need to display games that are not yet played, so add the
					// name of the team that has 'zaaldienst'.
					//
					else if(this.getWestrijdStatus() == 2)
					{
						let zaaldienstTeams = [];
						if(parseInt(wedstrijd['idTeamZaalDienst']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst'])}</div>`);
						}
						else if(parseInt(wedstrijd['idTeamZaalDienst_01']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst_01'])}</div>`);
						}
						if(parseInt(wedstrijd['idTeamZaalDienst_02']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst_02'])}</div>`);
						}
						if(parseInt(wedstrijd['idTeamZaalDienst_03']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst_03'])}</div>`);
						}
						if(parseInt(wedstrijd['idTeamZaalDienst_04']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst_04'])}</div>`);
						}
						if(parseInt(wedstrijd['idTeamZaalDienst_05']) > 0)
						{
							zaaldienstTeams.push(`<div class="clamp">${dataInstance.getTeamName(wedstrijd['idTeamZaalDienst_05'])}</div>`);
						}
						console.log('wedstrijd: ', wedstrijd, ' - zaaldienstTeams: ' , zaaldienstTeams);

						template += `<td class='zaaldienst'>${zaaldienstTeams.join('')}</td>`;
					}
					// In all other cases, just add an empty column to prevent
					// the table to break.
					//
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

		// After adding the template to the webcomponent, check attribute display. When set
		// to overview, this means besides the list of 'wedstrijden', also filters will be
		// available. Bind events to the <select> and <button> elements, used to filter the
		// list.
		//
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

	/**
	 * Returns integer (1 for played games, 2 for upcomming games) based on attribute 'status'
	 * for this webcomponent.
	 * @returns 
	 */
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

	/**
	 * Handles the change of a <select> element in the filters of the component. When a value is
	 * set, it will be added to the this.wedstrijdFilters-object, used to hold on which fields
	 * and which values the filters are set. When no value is found, the filter will be removed
	 * from the this.wedstrijdFilters-object.
	 *
	 * After this, we'll call  function this.filterWedstrijden() in order to filter the items
	 * in object 'this.wedstrijden', only to display the ones matching the set filters.
	 *
	 * @param {*} event 
	 */
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

	/**
	 * Clears all filers by resetting the value for all <select> and clearing the
	 * this.wedstrijdFilters-object.
	 *
	 * After this, we'll call  function this.filterWedstrijden() in order to filter the items
	 * in object 'this.wedstrijden', only to display the ones matching the set filters.
	 */
	clearFilters() {
	
		let selectElements = this.shadowRoot.querySelectorAll('select');
		selectElements.forEach(select => {
			select.value = '';
		});

		this.wedstrijdFilters = {};
		this.filterWedstrijden();
	}

	/**
	 * Loops through all 'wedstrijden' in the this.wedstrijden-object and check if the
	 * wedstrijd matches the set filters. If not, the row for the 'wedstrijd' will be hidden in
	 * the table, otherwise, the row will be displayed.
	 *
	 * When the row is displayed, variable visibleIndex will be increased and used to add class
	 * 'odd' or 'even' to the row, so that lines change color one after each other.
	 */
	filterWedstrijden()
	{	
		let visibleIndex = 0;
		this.wedstrijden.forEach(wedstrijd => {
			
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

	/**
	 * Check if a 'wedstrijd' matches the set filter(s). When no filters are set, we'll always
	 * return true, because no filters means all 'wedstrijden' has to be displayed. Otherwise,
	 * each filter must match the data from the 'wedstrijd' for it to be valid.
	 *
	 * For example, the this.wedstrijdFilters-object contains the following values...
	 *
	 *  {
	 *     datum: "2026-04-03"
	 *     idKlasse: 4
	 *     idTeam: 19
	 *   }
	 *
	 * ...all 3 filters must be found in the 'wedstrijd'-object as well. For 'datum' and
	 * 'idKlasse', the same value must be available. For 'idTeam', we'll check the values for
	 * 'idTeamThuis', 'idTeamUit' (and 'idTeamZaalDienst', 'idTeamZaalDienst_01',
	 * 'idTeamZaalDienst_02', 'idTeamZaalDienst_03', 'idTeamZaalDienst_04' and
	 * 'idTeamZaalDienst_05' when the 'wedstrijdStatus' is set to 2, meaning the game isn't
	 * played yet), when one of these matches, it'll be seen as a match.
	 *
	 * At the end of this function, we'll check if the amount of filters and the amount of
	 * matches are the same. If so, true will be returned, meaning the 'wedstrijd' contains all
	 * the values that's filtered on.
	 */
	wedstrijdMatchesFilter(wedstrijd)
	{
		let countFilters = Object.keys(this.wedstrijdFilters).length;
		if(countFilters == 0)
		{
			return true;
		}

		let countMatches = 0;
		for (const [key, value] of Object.entries(this.wedstrijdFilters))
		{
			if(value == '')
			{
				return;
			}

			switch(key)
			{
				// Custom handling for match on filter for 'idTeam', checking on
				// multiple fields in the 'wedstrijd' object to determine whether
				// or not a match was found.
				//
				case 'idTeam':
					if(
						(parseInt(wedstrijd['idTeamThuis']) == value)
						|| (parseInt(wedstrijd['idTeamUit']) == value)
					) {
						countMatches++;
					}
					else if(
						(this.getWestrijdStatus() == 2) && (
							(parseInt(wedstrijd['idTeamZaalDienst']) == value)
							|| (parseInt(wedstrijd['idTeamZaalDienst_01']) == value)
							|| (parseInt(wedstrijd['idTeamZaalDienst_02']) == value)
							|| (parseInt(wedstrijd['idTeamZaalDienst_03']) == value)
							|| (parseInt(wedstrijd['idTeamZaalDienst_04']) == value)
							|| (parseInt(wedstrijd['idTeamZaalDienst_05']) == value)
						)
					) {
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

	attributeChangedCallback(name, oldValue, newValue)
	{
		// called when one of the observed attributes listed below are modified
	}

	adoptedCallback()
	{
		// called when the element is moved to a new document
		// (happens in document.adoptNode, very rarely used)
	}

	static get observedAttributes()
	{
		return ['display', 'status', 'limit'];
	}

	// there can be other element methods and properties
}

// Let the browser know that <dzs-wedstrijden> is served by class dzsWedstrijden
//
customElements.define("dzs-wedstrijden", dzsWedstrijden);
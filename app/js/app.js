

/*
var exampleField = {
	// required
	
	// field label
	title: "",
	// field description
	description: "",
	// field name. acts as default _atts.name if not defined
	name: "",
	// group, select, textarea, input (default)
	type: "",
	
	// field element attributes
	// all legal attributes according to the element
	_atts: {
	// necessary attribute for "input" field type
	type: "", 
	},
	// string version of _atts - generated by preprocessing
	// attributes: '',
	
	// groups of other fields
	// array of field objects belonging to this group
	// (required by field type 'group')
	groupFields: [],
	// processed fields as html of a field group
	// groupHtml: '',
};
*/

var defaultFields = [
	// API Details
	{
		title: "API Details",
		description: "The API's URL arguments. <code>ex: http://example.com/{endpoint_base}/{post_type}[/{ID}]</code>",
		name: "endpointBase",
		type: "group",
		groupFields: [
			{
				title: "Endpoint base",
				description: "The top level argument for the endpoint.",
				name: "endpointBase",
				type: "input",
				_atts: {
					type: "text",
					class: "form-control",
					placeholder: "simple-json"
				},
			},
			{
				title: "Allowed Post Types",
				description: "Comma separated list of post_type slugs.",
				name: "allowedPostTypes",
				type: "input",
				_atts: {
					type: "text",
					class: "form-control",
					placeholder: "post,page"
				},
			},
		],
		_atts: {
			type: "",
		},
	},

	// Query Args
	{
		title: "Default Query Arguments",
		description: "Default WP_Query arguments for retrieving posts",
		name: "defaultQueryArguments",
		type: "group",
		groupFields: [
			{
				title: "Posts Per Page",
				description: "Number of items returned. Use -1 to return all results",
				name: "postsPerPage",
				type: "input",
				_atts: {
					type: "number",
					placeholder: 10,
					class: "form-control",
					min: -1
				},
			},
			{
				title: "Post Status",
				description: "Comma separated list of allowed post statuses",
				name: "postStatus",
				type: "input",
				_atts: {
					type: "text",
					placeholder: "publish",
					class: "form-control",
				},
			},
			{
				title: "Order By",
				description: "Data to order ",
				name: "orderBy",
				type: "select",
				options: [
					{ value: 'date', text: 'Date' },
					{ value: 'ID' },
					{ value: 'title', text: 'Title' }
				],
				_atts: {
					class: "form-control",
				}
			},
			{
				title: "Order",
				description: "Ascending or Descending?",
				name: "order",
				type: "select",
				options: [
					{ value: 'ASC' },
					{ value: 'DESC' }
				],
				_atts: {
					class: "form-control",
				}
			}
		],
	},

	// Generation Settings
	{
		title: "Download Details",
		description: "",
		type: "group",
		groupFields: [
			{
				title: "Download",
				description: "Choose the type of API you would like to generate. The plugin includes the necessary meta data, and the library requires you to execute the class::register() method",
				name: "downloadAs",
				type: "select",
				options: [
					{ value: 'library', text: 'Library' },
					{ value: 'plugin', text: 'Plugin' },
				],
				_atts: {
					class: "form-control",
				}
			},
			{
				title: "Plugin Details",
				description: "Meta data at the top of a plugin file",
				type: "group",
				name: "pluginDetails",
				groupFields: [
					{
						title: "Plugin Name",
						description: "Name your plugin. Make it something awesome!",
						name: "pluginName",
						type: "input",
						_atts: {
							type: "text",
							class: 'form-control',
							placeholder: "Simple JSON API",
						}
					},
					{
						title: "Plugin Slug",
						description: "Machine safe name for this plugin. lowercase letters and underscores only.",
						name: "pluginSlug",
						type: "input",
						_atts: {
							type: "text",
							class: 'form-control',
							placeholder: "simple_json_api",
						}
					},
					{
						title: "Plugin Description",
						description: "",
						name: "pluginDescription",
						type: "textarea",
						_atts: {
							class: 'form-control',
							placeholder: "Provides a simple JSON api for ...",
						}
					},
					{
						title: "Author Name",
						description: "Make sure people know who made this sweet plugin!",
						name: "authorName",
						type: "input",
						_atts: {
							type: "text",
							class: 'form-control',
							placeholder: "Your name here...",
						}
					},
				]
			},
		]
	},
	{
		title: "Download",
		description: "Do it!",
		name: "downloadAPI",
		type: "button",
		_atts: {
			type: "submit",
			class: "btn btn-primary",
		},
	}
];

var JSForms = JSForms || {};

(function( $, _ ){
	
	/**
	 *
	 * @type {{template: {groupWrapper: *, fieldWrapper: *, input: *, textarea: *, select: *}, processFields: Function, processField: Function, preprocessField: Function, renderField: Function, renderGroup: Function}}
	 */
	JSForms = {

		// something like models
		processedFields: function(){
			return {
				fields: [],
				html: ''
			};
		},
		blankField: function(){
			return {
				title: "",
				description: "",
				name: "",
				type: "",
				_atts: {
					type: ""
				},
			};
		},

		/**
		 * Template functions
		 */
		template: {
			groupWrapper: _.template( $( '#group-wrapper' ).html() ),
			fieldWrapper: _.template( $( '#field-wrapper' ).html() ),
			input:        _.template( $( '#field-input' ).html() ),
			textarea:     _.template( $( '#field-textarea' ).html() ),
			select:       _.template( $( '#field-select' ).html() ),
			button:       _.template( $( '#field-button' ).html() ),
		},
		
		/**
		 * Process an array of fields into an html string
		 *
		 * @param fields
		 * @returns { fields, html }
		 */
		processFields: function( fields ){
			var _this = this;
			var processed = this.processedFields();

			_.forEach( fields, function( field ){
				_this.preprocessField( field );
				_this.processField( field );
				
				processed.fields.push( field );
				processed.html+= field.wrapperHtml;
			});

			return processed;
		},

		/**
		 * Process a single field according to its rendering needs
		 *
		 * @param field
		 */
		processField: function( field ){
			// build html attributes
			this.makeAttributes( field );
			
			// render as needed
			if ( field.type == 'group' ) {
				this.renderGroup( field );
			}
			else {
				this.renderField( field );
			}
		},

		/**
		 * 
		 * @param field
		 */
		makeAttributes: function( field ){
			var attributes = [];
			_.forEach( field._atts, function( value, key ) {
				attributes.push( key + '="' + value + '"' );
			});
			field.attributes = attributes.join(' ');
		},

		/**
		 * Ensure the field is ready for processing
		 *
		 * @param field
		 * @returns {*}
		 */
		preprocessField: function( field ){
			// default values
			field.fieldHtml = '';
			field.wrapperHtml = '';
			field.type = field.type || 'input';
			field.attributes = '';
			field._atts = field._atts || {};
			field._atts.name = field.name;
			field._atts.id = field.name;
			
			// field classes
			var classes = [
				'field-type-' + field.type,
			];

			if ( ! _.isEmpty( field._atts.class ) ){
				classes.push( field._atts.class );
			}
			
			// type-specific defaults
			if ( field.type == 'input' ) {
				field._atts.type = field._atts.type || 'text';
			}
			else if ( field.type == 'group' ){
				field.groupFields = field.groupFields || [];
			}
			else if ( field.type == 'select' ){
				field.options = field.options || [];

				_.forEach( field.options, function( option, i ){
					option.text = option.text || option.value;
				});
			}

			field._atts.class = classes.join( ' ' );
		},

		/**
		 * Build HTML for fields
		 *
		 * @param fieldName
		 * @param field
		 * @returns {*}
		 */
		renderField: function( field ){
			// type is template function name
			if ( this.template[ field.type ] ) {
				field.fieldHtml = this.template[ field.type ]( field );
			}
			// default to input
			else {
				field.fieldHtml = this.template.input( field );
			}

			// add to wrapper
			field.wrapperHtml = this.template.fieldWrapper( field );
		},

		/**
		 * Build HTML for group of fields
		 *
		 * @param field
		 */
		renderGroup: function( field ){
			// group fields recurse
			field.processed = this.processFields( field.groupFields );
			field.wrapperHtml = this.template.groupWrapper( field );
		},
	};
	
	$( document ).ready(function(){
		var processed = JSForms.processFields( defaultFields );
		$( '#generator' ).html( processed.html );
		
		
		// this is where it gets ugly
		$('#pluginDetails').addClass('field-show-on');
		
		// events for now
		$( document ).on( 'change', '#downloadAs', function( event ){
			if ( 'plugin' == event.target.value ) {
				$('#pluginDetails' ).addClass('show');
			}
			else {
				$('#pluginDetails' ).removeClass('show');
			}
		} );
		
		$( document ).on( 'click', '#downloadAPI', function( event ){
			//$('#generator' ).submit();
			/*
			var submission = $('#generator' ).serializeArray();
			console.log(submission);
			
			$.ajax({
				url: '/index.php?ajax=download',
				method: 'POST',
				data: {
					submission: submission
				},
				success: function( response ){
					console.log(response);
				} 
			});
			*/
		} );
	});
	
})(jQuery, _);
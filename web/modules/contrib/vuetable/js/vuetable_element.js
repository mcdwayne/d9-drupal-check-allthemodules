//Bootstrap style CSS
var CssConfig = {
		  table: {
			    tableClass: 'table table-striped table-bordered',
			    ascendingIcon: 'glyphicon glyphicon-chevron-up',
			    descendingIcon: 'glyphicon glyphicon-chevron-down',
			    handleIcon: 'glyphicon glyphicon-menu-hamburger',
			    renderIcon: function(classes, options) {
			      return `<span class="${classes.join(' ')}"></span>`
			    }
			  },
			  pagination: {
			    wrapperClass: "pagination pull-right",
			    activeClass: "btn-primary",
			    disabledClass: "disabled",
			    pageClass: "btn btn-border",
			    linkClass: "btn btn-border",
			    icons: {
			      first: "",
			      prev: "",
			      next: "",
			      last: ""
			    }
			  }
			};

Vue.use(Vuetable);
new Vue({
  el: '#app',
  components:{
   'vuetable-pagination': Vuetable.VuetablePagination
  },
  data: {
  fields: drupalSettings.fields,
  css: CssConfig,
  url: drupalSettings.url,
  moreParams: {},
  },
  computed:{
  /*httpOptions(){
    return {headers: {'Authorization': "my-token"}} //table props -> :http-options="httpOptions"
  },*/
 },
 methods: {
    onPaginationData (paginationData) {
      this.$refs.pagination.setPaginationData(paginationData)
    },
    onChangePage (page) {
    	if (drupalSettings.page_start_from != null) {
    		if (page == 'next') {
        		this.moreParams[drupalSettings.page_param] = this.$refs.vuetable.currentPage + drupalSettings.page_start_from;
        	}
        	else if ( page == 'prev'){
        		this.moreParams[drupalSettings.page_param] = this.$refs.vuetable.currentPage - 2 + drupalSettings.page_start_from;
        	}
        	else {
        		this.moreParams[drupalSettings.page_param] = page - 1 + drupalSettings.page_start_from;
        	}
    	}
    	
      this.$refs.vuetable.changePage(page)
    },
    editRow(rowData){
      //Todo: codes for the clicked event of Edit button
    },
    deleteRow(rowData){
    	//Todo: codes for the clicked event of Deleted button
    },
    getSortParam(sortOrder){
   	  if (sortOrder[0]) {
   		  //For the REST API such as Druapl REST export view that the page number starts from 0
   	      //We have to override the page number parameter sent to the server
   		  if (drupalSettings.sort_order_param) {
   			this.moreParams[drupalSettings.sort_order_param] = sortOrder[0].direction.toUpperCase();
   			return sortOrder[0].sortField;
   		  }
   		  else {
   			return sortOrder[0].sortField + '|' + sortOrder[0].direction;
   		  }
   	  }
    },
  }
});
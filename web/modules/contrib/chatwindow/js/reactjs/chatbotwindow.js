import React from 'react';
import ReactDOM from 'react-dom';
import $ from "jquery";

class Chatboticon extends React.Component
{

	constructor(props)
	{
		
		super(props);
		this.state = {			
		};
		
		this.togglepopupwindow = this.togglepopupwindow.bind(this);
		
	}	


	togglepopupwindow(e) {
		
		e.preventDefault();
		$("#chatwindowchat").toggle();

	
	}	


	render() {


	
		return(
				<a href="#" onClick={this.togglepopupwindow}>     
	<img src={window.baseurlforchat+ window.modulepathchatwindow + "/images/chat_icon.png"} alt="chaticon" />
				</a>
			

		);	

	}	

}


class Chatbotwindow extends React.Component
{

	constructor(props)
	{
		
		super(props);
		this.state = {
			chatdata:[],
			boterror: ''			
		};	

		this.chatuserinput = React.createRef();
		this.chatdatauserserver = React.createRef();
		this.postuserchatdata = this.postuserchatdata.bind(this);
		this.renderuserchatdata = this.renderuserchatdata.bind(this);
		this.handleKeyPress = this.handleKeyPress.bind(this);

		/*this.renderbotchatImage = this.renderbotchatImage.bind(this);
		this.renderbotchatButton = this.renderbotchatButton.bind(this);
		this.renderbotchatmultimessage = this.renderbotchatmultimessage.bind(this);
		this.renderbotchatdata = this.renderbotchatdata.bind(this); 
		this.senddatatobot = this.senddatatobot.bind(this);
		this.renderbotchatdata = this.renderbotchatdata.bind(this);
		*/
		
	}


	togglepopupwindow(e) {
		
		e.preventDefault();
		$("#chatwindowchat").toggle();
		

	
	}


	renderuserchatdata(data) {	
	
		return <Userchatdata userchat={data} />
	
	}
	
	
	renderbotchatdata(data) {	
	
		return <Rasaresponsedata botreply={data} />
	
	}
	
	renderbotchatmultimessage(data) {	
	
		return <Eachbotresponceforarray botreply={data} />
		
	
	}
	
	renderbotchatImage(data) {
		
		return <EachbotresponceImageArray botreply={data} />
		
	}

	renderbotchatButton(data) {

		return <EachbotresponceButtonArray buttondetail={data} Chatbotwindowobj={this}/>

	}

	renderbotchatLink(data) {

		return <EachbotresponceLinkArray linkdetail={data} Chatbotwindowobj={this}/>

	}	
	
	scrollToBottom() {
	
		this.chatdatauserserver.current.scrollTop = this.chatdatauserserver.current.scrollHeight;
    }
	
	componentDidMount() {
	  this.scrollToBottom();
	}

	componentDidUpdate() {
	  this.scrollToBottom();
	}
	
	handleKeyPress (e) {
			
		if(e.key == 'Enter'){
			e.preventDefault();			
			this.postuserchatdata(e);
		}	
	
	}
	
	
	postuserchatdata(e) {
		

		
		
		let tempchatuserinput = this.chatuserinput.current.innerText;
		
		
		tempchatuserinput = tempchatuserinput.replace(/\r?\n|\r/g,"")
		
		// if the text is empty then don't push the data
		if(tempchatuserinput.trim().length < 1)
		{
			return;

		}
		let postdata =  {query:this.chatuserinput.current.innerText};
		
		//let reactjsparent = this; 
		
		// user data
		let userdata = this.renderuserchatdata(this.chatuserinput.current.innerText)	
		
		this.state.chatdata.push( userdata );
		this.setState({
			chatdata:this.state.chatdata
		 });
		 
		 // seperate the post data as there might be other post from other logic
		 this.senddatatobot(postdata);
		 this.chatuserinput.current.innerText = '';
		
	}
	
	
	
	
	
		renderbasedontype(data,reactjsparent,eachmessage)
		{

				
					let tempbotdata = [];
					
					
					let typeofdata = data.botreply[eachmessage].substring(0,6);
					

					switch(typeofdata)
					{
						
						case 'Image:':
						
								data.botreply[eachmessage] = data.botreply[eachmessage].replace('Image:','');
								tempbotdata.push(reactjsparent.renderbotchatImage(data.botreply[eachmessage]));
						
						break;
						
						case '{"mess':
						
								
								let jsonobj = JSON.parse(data.botreply[eachmessage]);
								
								
								
								if(jsonobj.hasOwnProperty('button'))
								{

							
							
									

										// show section
									if(data.botreply[eachmessage].indexOf('"message":"Select the section"') !== -1 )
									{

										console.log(jsonobj);
								
										jsonobj.button = $(".cwsection").map(function() {	

											//console.log(this.hasAttribute("role"));
											console.log(!this.hasAttribute("cwsectionfieldname"));
											let databtnmain = {};
											let datasection = {};
											//datasection["payload"] = {};
											
											if(this.hasAttribute("cwsectionfieldname"))
											{
											
												datasection["sectionfieldname"] = this.getAttribute("cwsectionfieldname");
												
											
											}
											
											if(this.hasAttribute("cwsectionfieldvalue"))
											{
											
												datasection["sectionfieldvalue"] = this.getAttribute("cwsectionfieldvalue");
												
											
											}
											
											
											if(this.hasAttribute("cwsectiontitle"))
											{
											
												databtnmain['title'] = this.getAttribute("cwsectiontitle");
												
											
											}
											

											let datapayload	= JSON.stringify(datasection);
											
											
											
											databtnmain['payload'] =  '/selectsections'+ datapayload;
											
											
											
											
											//data  =  JSON.stringify(data).replace(/\//g, '\\/');
											
											return databtnmain;
										}).get(); 



										
										jsonobj.button = JSON.stringify(jsonobj.button).replace(/\//g, '\\/');
										
										if(jsonobj.button == '[]')
										{

									
											//"{"message":"Select the section","button":[{"payload":"\/selectsections{\"sectionfieldname\":\"fieldname\",\"sectionfieldvalue\":\"fieldvalue\"}","title":"Section Name"}]}"
													let tempbutton = {};
											tempbutton.payload = "\/selectsections{\"sectionfieldname\":\"terminatetheform\",\"sectionfieldvalue\":\"terminatetheform\",\"nodepagepathornodeid\":\"terminatetheform\"}";
											
											
											tempbutton.title = 'Terminate';
											
											jsonobj.button = new Array(tempbutton);
											
											jsonobj.message = 'There are no section defined in the page. Click on the link to terminate the further steps'
									
										}
										else {
											
												jsonobj.button = JSON.parse(jsonobj.button);
											
											
										}	


									}
									
									
									tempbotdata.push(reactjsparent.renderbotchatmultimessage(jsonobj.message));

									
									
									for(var eachbutton in jsonobj.button)
									{
										


										console.log('inside for');
										console.log(jsonobj.button[eachbutton]);
										
										tempbotdata.push(reactjsparent.renderbotchatButton(jsonobj.button[eachbutton]));


									}
									
								}
								else if(jsonobj.hasOwnProperty('link'))
								{
									
									for(var eachlink in jsonobj.link)
									{
										


										
										
										tempbotdata.push(reactjsparent.renderbotchatLink(jsonobj.link[eachlink]));


									}
									
									
								}

								
								//console.log(jsonobj.button)
								/*if(jsonobj.button)
								{

								}*/
						break;
								
						default:
						
						
						
								tempbotdata.push(reactjsparent.renderbotchatmultimessage(data.botreply[eachmessage]));
							
							break;
						
					}
					
					
					
					// add the bot wrapper for reply look
					let botdata = reactjsparent.renderbotchatdata(tempbotdata);
					
					// due to array and string data type issue of botdata
					reactjsparent.state.chatdata.push(botdata);						
					reactjsparent.setState({
						chatdata:reactjsparent.state.chatdata
					 });
					
					return tempbotdata;
					
		}
	
	
	
		
	senddatatobot(postdata)	
	{
		
		
			let reactjsparent = this;
		
			$.post(window.baseurlforchat+"index.php/admin/chatwindow/ajax/chatdata", postdata, function(data)	{
					
			})
			.done(function(data) {
				
				

				console.log(data);
				// check if there is any reply from bot				
				if(data.botreply != '')				
				{

					
					//new Object();
					
					
					if(Array.isArray(data.botreply))
					{
						
						
				
						
						
						for(var eachmessage in data.botreply)
						{
							
							
							reactjsparent.renderbasedontype(data,reactjsparent,eachmessage);
							
							
						}
						
					}
					else {	
					
					
						let typeofdata = data.botreply.substring(0,6);
					
						
						if(typeofdata == '{"mess')
						{
							
							
							
							
							
							let jsonobj = JSON.parse(data.botreply);
							
							
							let tempdataforeachmessage = {botreply:[]};
							//let tempdataforeachmessage = {};
							
							
							 tempdataforeachmessage.botreply[0] = data.botreply;
							 
							
							reactjsparent.renderbasedontype(tempdataforeachmessage,reactjsparent,0);
							
							
							
						}
						else {
					
							let botdataSinglereply = reactjsparent.renderbotchatdata(data.botreply);
							
							
							reactjsparent.state.chatdata.push(botdataSinglereply);
							
							reactjsparent.setState({
								chatdata:reactjsparent.state.chatdata
							 });
						 
						} 

					}	
						

				
				}
				else if(data.error != '')
				{

					reactjsparent.state.boterror = data.error;
					reactjsparent.setState({
						boterror:reactjsparent.state.boterror
					 });

				}
				
			
				
			 })
			  .fail(function(data) {
				console.log( "error " );
				console.log( data );
				console.log(JSON.stringify(data));
			  });
			  
			  
			  //console.log(this.chatuserinput);
			  //this.chatuserinput.current.replace(/(\r\n|\n|\r)/gm,"");

		
	}	


	render() {
	
		return(
				<div className="chatwindowchatcontainer">
					<div className="chatwindowchatdataheader"> <a href="#" onClick={this.togglepopupwindow}> Chatwindow Bot </a> </div>
					<div ref={this.chatdatauserserver} className="chatwindowchatdata">{this.state.chatdata}</div>
					<div className="chatwindowboterror"> {this.state.boterror} </div>
					 <div className="chatwindowchatdatabottom"> 
						<div contentEditable="true" defaultValue="" onKeyPress={this.handleKeyPress} ref={this.chatuserinput} id="userchatdata"> </div> 
						<div className="chatwindowbtncont"> <button  type="button" onClick={this.postuserchatdata} className="btn btn-primary btn-sm">Send</button> </div>
					 </div> 
				</div>
			

		);	

	}	

}



class Userchatdata extends React.Component
{
	
	
	render(){	
		return(
			

				<div className="chatwindouwserchatdata"> 
					<div className="chattitle"> You </div>
					<div className="chatborder">
						{this.props.userchat} 
					</div>
				
				</div>
	
			
			
		);
	}
	
}

class Rasaresponsedata extends React.Component
{
	
	
	render(){	
		return(
			

				<div className="chatrasaresponsedata">
					<div className="chattitle"> Bot </div>
					<div className="chatborder">
						{this.props.botreply}  
					</div>
				 </div>

			
			
		);
	}
	
}
	

class Eachbotresponceforarray	extends React.Component
{
	
		render(){
			
			
				return (
				
				<div className="botdatarray" > {this.props.botreply} </div>
				
				);
			
		}	
	
}

class EachbotresponceImageArray	extends React.Component
{
	
		render(){
			
			

			
			
				return (
				
				<div className="botdatarray" > <img src={this.props.botreply} alt="image" /> </div>
				
				);
			
		}	
	
}


class EachbotresponceButtonArray	extends React.Component
{
	
	
	constructor(props)
	{
		
		super(props);

		this.buttondetail = this.buttondetail.bind(this);
		this.state = {
			disableclass : ''
		};	
	}
	
	buttondetail(e,payload)
	{
		
		e.preventDefault();
		
		//let ObjChatbotwindow = new Chatbotwindow();		
		
		
		let postdata =  {query:payload};
		this.props.Chatbotwindowobj.senddatatobot(postdata);
		
		/*
		this.state.disableclass = ' disabled ';
		
		this.setState({
			disableclass : this.state.disableclass
			
		});
		
		
		
		console.log($(e).attr('class'));

		$(e).addClass('disabled');
		$(e).next().addClass('disabled');
		*/
		
		$('.usereplaceimage').addClass('disabled');
	
	}
	
		render(){
			
			


			
				return (
				

					<a class="btn btn-outline-secondary usereplaceimage" style={{margin:'10px'}} onClick={(e) => this.buttondetail(e,this.props.buttondetail.payload)} href="#" role="button" >{this.props.buttondetail.title}</a>
				
				);
			
		}	
	
}



class EachbotresponceLinkArray	extends React.Component
{
	
	
	constructor(props)
	{
		
		super(props);

		this.buttondetail = this.buttondetail.bind(this);
		this.state = {
			disableclass : ''
		};	
	}
	
	buttondetail(e,payload)
	{
		
		e.preventDefault();
		
		//let ObjChatbotwindow = new Chatbotwindow();		
		
		
		let postdata =  {query:payload};
		this.props.Chatbotwindowobj.senddatatobot(postdata);
		
		/*
		this.state.disableclass = ' disabled ';
		
		this.setState({
			disableclass : this.state.disableclass
			
		});
		
		
		
		console.log($(e).attr('class'));

		$(e).addClass('disabled');
		$(e).next().addClass('disabled');
		*/
		
		$('.usereplaceimage').addClass('disabled');
	
	}
	
		render(){
			
			


			
				return (
				

					 <div> <a  style={{margin:'10px'}}  href={this.props.linkdetail.url} class="btn btn-primary" role="button" target={this.props.linkdetail.target} >{this.props.linkdetail.linktitle}</a></div>
				
				);
			
		}	
	
}




	

//return ();


ReactDOM.render( <Chatboticon />,document.getElementById('chatwindowchaticon'));
ReactDOM.render( <Chatbotwindow />,document.getElementById('chatwindowchat'));
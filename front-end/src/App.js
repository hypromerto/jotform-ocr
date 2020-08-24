import React, { Component} from 'react';
import jotform_icon from './jotform-icon.jpg';
import './App.css';
import axios from 'axios';

class App extends Component{

  constructor(props) {
    super(props);
    this.state = {
      selectedFile : null,
      createForm: true,
      submitted: false,
      formID: '',
      formURL: '',
      success: '',
      buildURL: ''
    }

    this.handleChangeFormID = this.handleChangeFormID.bind(this);
  }

  fileSelect = event => {
    this.setState({selectedFile: event.target.files[0]})
  }

  async handleFormSubmit( event ) {
    event.preventDefault();
    const fd = new FormData();
    fd.append('image', this.state.selectedFile, this.state.selectedFile.name);
    fd.append('createForm', this.state.createForm);
    fd.append('formID', this.state.formID);
    await axios.post('http://localhost/jotform/upload.php', fd
    ).then(res=>
    {
      if (this.state.createForm){
        this.setState(
          { formURL: res["data"]["formURL"],
            buildURL: "https://jotform.com/build/".concat(
              res["data"]["formURL"].substring(res["data"]["formURL"].lastIndexOf('/') + 1))
          });
      }
    }
    );
    this.setState({submitted: true});
  }

  handleChangeRadio(submit){
    this.setState({createForm: submit, submitted: false, formID: ''});    
  }

  handleChangeFormID(event){
    this.setState({formID: event.target.value})
  }


  render(){

    const { createForm } = this.state;

    const { submitted } = this.state;

    const { formURL } = this.state;

    const { buildURL } = this.state; 

    const form_id_div = <div className="form-id-box">
    <label>Enter form ID of the form you want to submit to</label>
    <input type="text" value={this.state.formID} onChange={this.handleChangeFormID}></input> 
    </div>

    const result_url_div = <div className="url-field">

      <label>
        You can find your new form at:
      </label>

      <textarea>
        {formURL}
      </textarea>


      <div className="btn-group">
        <form action= {formURL} target="_blank">
          <button className="view-button" type="submit">
            View Form
          </button>
        </form>
        <form action= {buildURL} target="_blank">
          <button className="edit-button" type="submit">
            Edit Form
          </button>
        </form>
      </div>
    </div>

    const submission_div = <div className="submission-result">
      <label>
        Your submission has been received.
      </label>
    </div>


    return (
      <div className="App">
        <img src={jotform_icon}></img>
        <p>JotForm OCR</p>
          <div>
            <form action="/jotform/upload.php">
                <label>
                    Select an option:
                </label>
                <div className="radio-box">
                  <input type="radio" value="option1" checked={this.state.createForm} onClick={(e) => this.handleChangeRadio(true)}/>
                  <label>Create a Form</label>
                  <br/>
                  <input type="radio" value="option1" checked={!this.state.createForm} onClick={(e) => this.handleChangeRadio(false)} />
                  <label>Submit to a Form</label>
                </div>

                {createForm === false && form_id_div}
              <label>  Select image to upload</label>
              <input type="file" onChange={this.fileSelect}/>
              <input type="submit" onClick={e => this.handleFormSubmit(e)} value="Submit" />


            </form >
          </div>
          {submitted === true && createForm === false && submission_div}

          {submitted === true && createForm === true && result_url_div}

      </div>
    );
  }
}

export default App;

import logo from './logo.svg';
import './App.css';

import React, { PureComponent,Component } from 'react';

class Squere extends Component {
    state = {
        value:null
    }
    handler=()=>{
       this.setState({value:'x'});
    }
    
    render() {
        return ( < button onClick={this.handler} > {this.state.value} </button>);
        }
    }
    const status = 'Next player: X';
    class Board extends Component {
        state = {}
       
        renderSquare(i){
          return <Squere className="abs" value={i} />
        }
        render() {
           return (
            <div>
                <div className="status">{status}</div>
                <div className="board-row">
                    {this.renderSquare(0)}
                    {this.renderSquare(1)}
                    {this.renderSquare(2)}
                </div>
                <div className="board-row">
                    {this.renderSquare(3)}
                    {this.renderSquare(4)}
                    {this.renderSquare(5)}
                </div>
                <div className="board-row">
                    {this.renderSquare(6)}
                    {this.renderSquare(7)}
                    {this.renderSquare(8)}
                </div>
          </div>
           );
        }
    }
    class Game extends Component {
   
        render() {
           return ( <div className="game">
           <div className="game-board">
             <Board />
           </div>
           <div className="game-info">
             <div>{/* status */}</div>
             <ol>{/* TODO */}</ol>
           </div>
         </div>);
        }
    }

    export default Game;
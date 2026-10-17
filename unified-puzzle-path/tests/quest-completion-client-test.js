const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync(require('path').join(__dirname, '../index.html'), 'utf8');
const finish = source.slice(source.indexOf('    let finishSaveInFlight = false;'), source.indexOf('    function formatTime('));
function fixture() {
  const elements = {finishButton:{disabled:false,textContent:''},finishContainer:{appendChild(e){elements[e.id]=e;}}};
  const pending = new Map(); let clearCount=0;
  const scope = {console, Date, JSON, Error, completedClues:3,totalClues:3,startTime:new Date(Date.now()-3600000),huntData:{hunt_id:9,hunt_name:'Fixture'},verifiedBookingData:{booking_code:'FIXTURE'},timerInterval:1,
    document:{getElementById(id){return elements[id]||null;},createElement(){return {setAttribute(){},style:{}};}},
    localStorage:{getItem:k=>pending.get(k)||null,setItem:(k,v)=>pending.set(k,v),removeItem:k=>pending.delete(k)},
    sessionStorage:{setItem(){}},window:{location:{href:'quest.html'}},formatTime:()=> '1:00:00',clearInterval(){},saveQuestState(){},stopAutoSave(){},clearQuestState(){clearCount++;},sendQuestEvent:async()=>({success:true,progress:{quest_status:'completed'}})};
  vm.createContext(scope);vm.runInContext(finish,scope);
  return {scope,elements,pending,clears:()=>clearCount};
}
function check(ok,message){if(!ok)throw Error(message);}
(async()=>{
 const clueCountScope={console,totalClues:6,cluesData:[],document:{getElementById(){return this.count||(this.count={textContent:6});}},fetch:async()=>({json:async()=>({success:true,clues:Array.from({length:8},(_,id)=>({id}))})}),createClueElements(){},showMessage(){throw Error('Clue load failed');}};
 vm.createContext(clueCountScope);vm.runInContext(source.slice(source.indexOf('    async function loadClues('),source.indexOf('    function createClueElements(')),clueCountScope);await clueCountScope.loadClues(9);check(clueCountScope.totalClues===8&&clueCountScope.document.count.textContent===8,'Stale quest summary count used instead of actual clues');
 let f=fixture();await f.scope.finishQuest();check(f.scope.window.location.href==='dashboard.php'&&f.clears()===1,'Confirmed completion did not redirect');
 f=fixture();f.scope.sendQuestEvent=async()=>{throw Error('offline');};await f.scope.finishQuest();check(f.scope.window.location.href==='quest.html'&&f.clears()===0&&!f.elements.finishButton.disabled&&f.pending.size===1,'Offline attempt lost progress');
 const cached=f.pending.get('puzzlepath-finish:FIXTURE');let attempts=0;f.scope.sendQuestEvent=async(a,d)=>{attempts++;check(JSON.stringify(d)===cached,'Retry changed finish time');return {success:true,progress:{quest_status:'completed'}};};await f.scope.finishQuest();check(f.clears()===1&&f.pending.size===0&&attempts===1,'Retry failed');
 f=fixture();f.scope.sendQuestEvent=async()=>({success:true});await f.scope.finishQuest();check(f.clears()===0&&f.scope.window.location.href==='quest.html','Unconfirmed backend result accepted');
 f=fixture();let resolve,calls=0;f.scope.sendQuestEvent=()=>{calls++;return new Promise(r=>resolve=r);};const first=f.scope.finishQuest();await f.scope.finishQuest();check(calls===1,'Double click sent duplicate request');resolve({progress:{quest_status:'completed'}});await first;
 f=fixture();f.scope.completedClues=2;await f.scope.finishQuest();check(f.clears()===0&&f.scope.window.location.href==='quest.html','Incomplete quest finished');
 console.log('PASS: confirmed redirect, offline retention, frozen retry, unconfirmed response, double-click guard, incomplete quest.');
})().catch(e=>{console.error(e);process.exitCode=1;});

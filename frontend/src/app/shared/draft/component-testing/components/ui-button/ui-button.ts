import { Component, computed, input, output } from '@angular/core';

@Component({
  selector: 'app-ui-button',
  imports: [],
  templateUrl: './ui-button.html',
  styleUrl: './ui-button.css'
})
export class UiButton {
  variant = input<'primary' | 'secondary' | 'ghost'>();
  type = input<'button' | 'submit'> ('button');
  disabled = input(false);
  loading = input(false);
  onClick = output<any>();

  buttonClasses = computed(()=>{
    return `btn btn--${this.variant()}`;
  })

  buttonPressed = () => {
    console.log("button actived");
    this.onClick.emit(18);
  }
}

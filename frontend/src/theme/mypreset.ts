import { definePreset } from "@primeuix/themes";
import Aura from '@primeuix/themes/aura';
const MyPreset = definePreset(Aura, {
  semantic: {
    colorScheme: {
      light: {
        box: {
          background: '{surface.50}',
          color: '{surface.900}',
          border: '{surface.700}'
        }
      },
      dark: {
        box: {
          background: '{surface.800}',
          color: '{surface.0}',
          border: '{surface.300}'
        }
      }
    }
  }
});

export default MyPreset;
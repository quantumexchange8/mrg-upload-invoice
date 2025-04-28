import { reactive } from "vue";

// Define a type for the toast object
interface Toast {
  title: string;
  message: string;
  type: string;
  // Add other properties of toast as needed
}

export default reactive({
  items: [] as Array<{ key: symbol } & Toast>, // Array of toast objects with a key
  add(toast: Toast) {
    this.items.unshift({
      key: Symbol(),
      ...toast,
    });
  },
  remove(index: number) {
    this.items.splice(index, 1);
  },
});

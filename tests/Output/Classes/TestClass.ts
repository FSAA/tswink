import { uuid } from "uuidv4"
import EventType from "./EventType"
import Introduction from "./Introduction"
// <non-auto-generated-import-declarations>
import TestImport from "./TestImport"
// </non-auto-generated-import-declarations>

export default class TestClass {

    public static readonly TEST_CONST: number = 45.6;
    public birth_amount?: number;
    public breeding_event_id?: number;
    public breeding_projection_chart_week?: number;
    public created_at?: Date;
    public cycle?: number;
    public deleted_at?: Date;
    public end_date?: Date;
    public event_type?: EventType;
    public event_type_id?: number;
    public female_cycle?: number;
    public female_inventory?: number;
    public female_inventory_order?: number;
    public female_type_id?: number;
    public id?: number;
    public introductions: Introduction[];
    public inventory_change?: number;
    public parent_event_id?: number;
    public reproduction_sold_amount?: number;
    public simulation_breed_group_id?: number;
    public start_date?: Date;
    public testAccessor?: any;
    public updated_at?: Date;
    public uuid?: string = uuid();
    
    constructor(init?: Partial<TestClass>) {
        Object.assign(this, init);
        this.created_at = init?.created_at ? Date.parseEx(init.created_at) : undefined;
        this.deleted_at = init?.deleted_at ? Date.parseEx(init.deleted_at) : undefined;
        this.end_date = init?.end_date ? Date.parseEx(init.end_date) : undefined;
        this.event_type = init?.event_type ? new EventType(init.event_type) : undefined;
        this.introductions = init?.introductions ? init.introductions.map(v => new Introduction(v)) : [];
        this.start_date = init?.start_date ? Date.parseEx(init.start_date) : undefined;
        this.updated_at = init?.updated_at ? Date.parseEx(init.updated_at) : undefined;
    }
    
    // <non-auto-generated-class-declarations>
    public testAttribute: any;
    public testFunction(): any {
    
    }
    // </non-auto-generated-class-declarations>
}
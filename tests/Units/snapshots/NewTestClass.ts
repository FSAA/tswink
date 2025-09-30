import type BaseModel from './BaseModel'
import type TestClassTagPivot from './TestClassTagPivot'
import type NewUser from './NewUser'
import type NewTag from './NewTag'

// <non-auto-generated-import-declarations>
import TestImport from "./TestImport"
// </non-auto-generated-import-declarations>

export default interface NewTestClass extends BaseModel {
    anyArray?: Array<any>;
    assignment?: TestClassTagPivot;
    associativeArray?: { stringProperty: string, numberProperty: number, complexProperty: { key: string }, subArray: { [key: string]: string } };
    complexArray?: { [key: number]: { foo: boolean } };
    created_at?: string;
    deepStringArray?: Array<Array<string>>;
    id?: number;
    name?: string;
    nullable_student2_count?: number;
    nullable_student_count?: number;
    stringArray?: Array<string>;
    stringOrIntAccessor?: string | number;
    student_count?: number;
    students?: NewUser[];
    tags?: NewTag[];
    tesAnyAccessor?: any;
    testAccessor?: string;
    test_nullable_any_count?: any;
    tswinkOverride?: string;
    updated_at?: string;
    user?: NewUser;
    value?: number;

    // <non-auto-generated-class-declarations>

    public testAttribute: any;
    public testFunction(): any {

    }

    // </non-auto-generated-class-declarations>
}

// <non-auto-generated-code>

// </non-auto-generated-code>

export const NewTestClassConstants = {
    TEST_CONST: 45.6,
    TEST_CONST_ARRAY: ['test',123,true],
    TEST_CONST_STRING: 'test',
    phpQualifiedClassName: 'TsWinkTests\\Units\\Input\\TestClass',
}
